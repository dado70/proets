<?php
declare(strict_types=1);

namespace ProETS\Controllers;

use ProETS\Core\Auth;
use ProETS\Core\Controller;
use ProETS\Core\Migrator;
use ProETS\Core\Router;
use ProETS\Core\Session;
use ProETS\Core\Version;
use ProETS\Core\View;

class UpdateController extends Controller
{
    private const GITHUB_API = 'https://api.github.com/repos/dado70/proets/releases/latest';
    private const CACHE_FILE  = '/logs/update_check.json';
    private const CACHE_TTL   = 86400; // 24 ore

    private function requireSuperadmin(): void
    {
        $this->requireAuth();
        if ((Auth::user()['ruolo'] ?? '') !== 'superadmin') {
            Router::abort(403, 'Accesso riservato al superadmin.');
        }
    }

    public function index(array $params = []): void
    {
        $this->requireSuperadmin();

        $results = null;

        if ($this->isPost()) {
            $this->csrfCheck();
            $results = Migrator::runAll();
            if (!empty($results)) {
                $ok = array_filter($results, fn($r) => $r['ok']);
                if (count($ok) === count($results)) {
                    $this->flash('success', count($results) . ' migration(i) applicate con successo.');
                } else {
                    $this->flash('error', 'Alcune migrazioni non sono state applicate. Controlla il log qui sotto.');
                }
            } else {
                $this->flash('info', 'Nessuna migrazione pendente da eseguire.');
            }
        }

        $currentVersion = Version::current();
        $dbVersion      = Version::dbVersion();
        $pending        = Migrator::getPending();
        $applied        = Migrator::getApplied();
        $latestRelease  = $this->fetchLatestRelease();

        View::render('superadmin/aggiornamenti', [
            'pageTitle'      => 'Sistema — Aggiornamenti',
            'company'        => Auth::company(),
            'currentUser'    => Auth::user(),
            'esercizioAttivo'=> (int)(Auth::company()['esercizio_corrente'] ?? date('Y')),
            'csrf'           => Session::csrf(),
            'currentVersion' => $currentVersion,
            'dbVersion'      => $dbVersion,
            'pending'        => $pending,
            'applied'        => $applied,
            'latestRelease'  => $latestRelease,
            'results'        => $results,
        ]);
    }

    /**
     * Recupera l'ultima release da GitHub API con cache 24h.
     * Fallisce gracefully: ritorna null se non raggiungibile.
     */
    private function fetchLatestRelease(): ?array
    {
        $cacheFile = dirname(__DIR__, 2) . self::CACHE_FILE;

        // Controlla cache
        if (file_exists($cacheFile)) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached) && isset($cached['_fetched_at'])) {
                if (time() - $cached['_fetched_at'] < self::CACHE_TTL) {
                    unset($cached['_fetched_at']);
                    return $cached ?: null;
                }
            }
        }

        // Fetch da GitHub
        if (!function_exists('curl_init')) return null;

        $ch = curl_init(self::GITHUB_API);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_USERAGENT      => 'ProETS/' . Version::current(),
            CURLOPT_HTTPHEADER     => ['Accept: application/vnd.github.v3+json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$body) return null;

        $data = json_decode((string)$body, true);
        if (!is_array($data) || empty($data['tag_name'])) return null;

        $result = [
            'tag_name'    => $data['tag_name'],
            'name'        => $data['name'] ?? $data['tag_name'],
            'html_url'    => $data['html_url'] ?? '',
            'published_at'=> $data['published_at'] ?? '',
            'body'        => $data['body'] ?? '',
            '_fetched_at' => time(),
        ];

        // Salva in cache (ignora errori di scrittura)
        @file_put_contents($cacheFile, json_encode($result));

        unset($result['_fetched_at']);
        return $result;
    }
}
