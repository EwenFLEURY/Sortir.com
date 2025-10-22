<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

final class UrlService
{
    /**
     * Renvoi le referer pour revenir à la page précédente
     * @param Request $request
     * @param bool $safe
     * @return string|null
     */
    public function getReferer(Request $request, bool $safe = true): string|null {
        $referer = $request->headers->get('referer');
        if ($safe && !$this->isSafeInternalUrl($referer, $request)) {
            return null;
        }
        return $referer;
    }

    /**
     * Met à jour le Form return to pour pouvoir retourner à la page précédente
     * @param Request $request
     * @param Session $session
     * @return void
     */
    public function setFormReturnTo(Request $request, Session $session): void {
        // Sur l'affichage du formulaire (GET), on mémorise la page d'origine
        if ($request->isMethod('GET')) {
            $from = $this->getReferer($request);
            if ($this->isSafeInternalUrl($from, $request)) {
                $session->set('form.return_to', $from);
            }
        }
    }

    /**
     * Récuéprer le Form return To pour pouvoir retourner à la page précédente
     * @param Session $session
     * @return string|null
     */
    public function getFormReturnTo(Session $session): ?string
    {
        return $session->remove('form.return_to');
    }

    /**
     * Vérfie si l'url est une url interne au site
     * @param string|null $url
     * @param Request $request
     * @return bool
     */
    public function isSafeInternalUrl(?string $url, Request $request): bool
    {
        if (!$url) {
            return false;
        }

        // URL relative interne
        if (str_starts_with($url, '/')) {
            return true;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $host = $parts['host'] ?? null;
        $scheme = $parts['scheme'] ?? null;
        $port = $parts['port'] ?? $request->getPort();

        return $host === $request->getHost() &&
            $scheme === $request->getScheme() &&
            $port === $request->getPort();
    }
}