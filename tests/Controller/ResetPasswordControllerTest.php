<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final class ResetPasswordControllerTest extends WebTestCase
{
    public function testForgotPasswordRequestPageLoads(): void
    {
        $client = static::createClient();

        $client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
    }

    public function testSubmittingUnknownEmailRedirectsToCheckEmail(): void
    {
        $client = static::createClient();

        // 1) Charger la page pour récupérer le formulaire (et son token CSRF)
        $crawler = $client->request('GET', '/reset-password');
        $this->assertResponseIsSuccessful();

        // 2) Récupérer le premier champ email du formulaire et son nom
        $emailInput = $crawler->filter('input[type="email"]')->first();
        $this->assertGreaterThan(
            0,
            $emailInput->count(),
            'Le formulaire de demande doit contenir un champ email.'
        );

        // 3) Déterminer le nom réel de l’input pour l’alimenter dynamiquement
        $emailFieldName = $emailInput->attr('name');
        $this->assertNotEmpty(
            $emailFieldName,
            'Impossible de déterminer le nom du champ email.'
        );

        // 4) Obtenir l'objet Form à partir du formulaire (et non de l'input)
        $client->submitForm('Réinitialiser', [
            'reset_password_request_form[email]' => 'unknown.user@example.test',
        ]);

        // 5) On ne révèle pas l’existence d’un utilisateur : redirection systématique
        //    vers /reset-password/check-email
        $this->assertResponseRedirects('/reset-password/check-email', 302);

        // 6) Suivre la redirection et vérifier la page
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }

    public function testResetWithoutTokenReturns404(): void
    {
        $client = static::createClient();

        // Appel direct sans token en URL et sans token en session => 404
        $client->request('GET', '/reset-password/reset');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testResetWithTokenIsStoredAndInvalidTokenRedirectsBackToRequest(): void
    {
        $client = static::createClient();

        // 1) Premier GET avec token dans l’URL: il est stocké en session et on est
        //    redirigé vers la même route sans paramètre.
        $client->request('GET', '/reset-password/reset/abc123');
        $this->assertResponseRedirects('/reset-password/reset', 302);

        // 2) Suivre la redirection: cette fois le contrôleur lit le token de session,
        //    tente de le valider et, comme le token est invalide, le helper réel lève
        //    une exception et le contrôleur redirige
        //    vers la page de demande initiale (/reset-password).
        $client->followRedirect();
        $this->assertResponseRedirects('/reset-password', 302);

        // 3) Optionnel: on peut suivre et vérifier que la page charge correctement
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
    }
}