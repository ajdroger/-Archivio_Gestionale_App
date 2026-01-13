<?php

namespace MCAG\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Mustache_Engine;

class PolicyController
{
    private Mustache_Engine $engine;

    public function __construct(Mustache_Engine $engine)
    {
        $this->engine = $engine;
    }

    public function privacy(Request $request, Response $response): Response
    {
        $policyContent = <<<HTML
            <div class="card bg-dark text-white border-secondary shadow-lg">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-primary"><i class="fa-solid fa-user-shield me-2"></i>Informativa sulla Privacy</h1>
                    <p class="lead text-white-50">Ultimo aggiornamento: 25 Dicembre 2025</p>
                    <hr class="my-4 border-secondary">
                    
                    <section class="mb-5">
                        <h4 class="text-info">1. Titolare del Trattamento</h4>
                        <p class="text-white-50"><strong>MCAG - Militare Civile Archivio Gestionale</strong><br>
                        Email: <a href="mailto:privacy@mcag.it" class="link-light">privacy@mcag.it</a></p>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">2. Dati Raccolti</h4>
                        <p class="text-white-50">Raccogliamo e trattiamo i seguenti dati personali:</p>
                        <ul class="text-white-50">
                            <li><strong>Dati Anagrafici</strong>: Nome, Cognome, Codice Fiscale, Data di nascita, Luogo di nascita</li>
                            <li><strong>Dati di Contatto</strong>: Email (facoltativa), Telefono (facoltativo), Indirizzo</li>
                            <li><strong>Dati di Iscrizione</strong>: Matricola, Stato iscrizione, Date iscrizione e scadenza</li>
                            <li><strong>Documenti</strong>: Documenti di identità, Moduli di iscrizione, Ricevute pagamento</li>
                            <li><strong>Dati di Navigazione</strong>: Indirizzo IP, User agent, Timestamp accessi (solo per utenti autenticati)</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">3. Base Giuridica del Trattamento</h4>
                        <p class="text-white-50">Il trattamento dei dati è necessario per:</p>
                        <ul class="text-white-50">
                            <li><strong>Art. 6(1)(b) GDPR</strong>: Esecuzione del contratto di iscrizione all'associazione</li>
                            <li><strong>Art. 6(1)(c) GDPR</strong>: Adempimento obblighi legali (fiscali, contabili)</li>
                            <li><strong>Art. 6(1)(a) GDPR</strong>: Consenso per finalità di marketing (opzionale)</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">4. Finalità del Trattamento</h4>
                        <ol class="text-white-50">
                            <li>Gestione delle iscrizioni e rinnovi</li>
                            <li>Archiviazione documentale digitale</li>
                            <li>Comunicazioni associative obbligatorie</li>
                            <li>Adempimenti fiscali e contabili</li>
                            <li>Marketing associativo (solo con consenso esplicito)</li>
                        </ol>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">5. Conservazione dei Dati</h4>
                        <div class="table-responsive">
                            <table class="table table-dark table-bordered border-secondary">
                                <thead class="table-secondary text-dark">
                                    <tr>
                                        <th>Tipologia Dato</th>
                                        <th>Periodo di Conservazione</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dati anagrafici soci attivi</td>
                                        <td>Per tutta la durata dell'iscrizione + 10 anni (obblighi fiscali)</td>
                                    </tr>
                                    <tr>
                                        <td>Documenti contabili</td>
                                        <td>10 anni dalla cessazione (Art. 2220 C.C.)</td>
                                    </tr>
                                    <tr>
                                        <td>Consensi marketing</td>
                                        <td>Fino a revoca o 2 anni di inattività</td>
                                    </tr>
                                    <tr>
                                        <td>Log di accesso (audit)</td>
                                        <td>12 mesi (sicurezza informatica)</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">6. Diritti dell'Interessato (Art. 15-22 GDPR)</h4>
                        <p class="text-white-50">Hai il diritto di:</p>
                        <ul class="text-white-50">
                            <li>✅ <strong>Accesso</strong> (Art. 15): Ottenere copia dei tuoi dati personali</li>
                            <li>✅ <strong>Rettifica</strong> (Art. 16): Correggere dati inesatti</li>
                            <li>✅ <strong>Cancellazione</strong> (Art. 17): Richiedere la cancellazione definitiva</li>
                            <li>✅ <strong>Limitazione</strong> (Art. 18): Limitare il trattamento</li>
                            <li>✅ <strong>Portabilità</strong> (Art. 20): Ricevere i dati in formato machine-readable</li>
                            <li>✅ <strong>Opposizione</strong> (Art. 21): Opporti al trattamento per finalità di marketing</li>
                            <li>✅ <strong>Revoca Consenso</strong>: Revocare il consenso in qualsiasi momento</li>
                        </ul>
                        <div class="alert alert-dark border-secondary">
                            <h5 class="text-light">Come Esercitare i Tuoi Diritti</h5>
                            <ol class="text-white-50 mb-0">
                                <li>Accedi all'area riservata con le tue credenziali</li>
                                <li>Vai su <strong>Impostazioni → Privacy & GDPR</strong></li>
                                <li>Oppure invia email a: <a href="mailto:privacy@mcag.it" class="link-light">privacy@mcag.it</a></li>
                            </ol>
                        </div>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">7. Sicurezza dei Dati</h4>
                        <p class="text-white-50">Implementiamo le seguenti misure di sicurezza:</p>
                        <ul class="text-white-50">
                            <li>🔒 <strong>Crittografia HTTPS</strong> per tutte le comunicazioni</li>
                            <li>🔒 <strong>Password hashing</strong> con bcrypt</li>
                            <li>🔒 <strong>Autenticazione 2FA</strong> obbligatoria per amministratori</li>
                            <li>🔒 <strong>Audit logging completo</strong> di tutti gli accessi</li>
                            <li>🔒 <strong>Backup giornalieri</strong> cifrati</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">8. Comunicazione a Terzi</h4>
                        <p class="text-white-50">I tuoi dati <strong>NON vengono venduti</strong> a terze parti. Possono essere comunicati solo a:</p>
                        <ul class="text-white-50">
                            <li>Commercialista per obblighi fiscali</li>
                            <li>Fornitori IT per manutenzione server (solo su base need-to-know)</li>
                            <li>Autorità giudiziarie su richiesta legale</li>
                        </ul>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">9. Cookies</h4>
                        <p class="text-white-50">Il sito utilizza solo <strong>cookies tecnici essenziali</strong> (Sessione, CSRF). <strong>NO cookies di profilazione</strong> o tracking di terze parti.</p>
                        <a href="{{base_url}}/cookie-policy" class="btn btn-outline-info btn-sm">Leggi Cookie Policy Completa</a>
                    </section>

                    <section class="mb-5">
                        <h4 class="text-info">10. Modifiche</h4>
                        <p class="text-white-50">Questa informativa può essere aggiornata. Le modifiche sostanziali saranno comunicate via email.</p>
                    </section>
                    
                    <section>
                         <h4 class="text-info">11. Reclami</h4>
                         <p class="text-white-50">Hai il diritto di presentare reclamo al Garante Privacy (<a href="https://www.garanteprivacy.it" target="_blank" class="link-light">www.garanteprivacy.it</a>).</p>
                    </section>
                </div>
            </div>
HTML;

        $html = $this->engine->render('layout/layout', [
            'content' => $policyContent,
            'title' => 'Privacy Policy - MCAG',
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);
        $response->getBody()->write($html);
        return $response;
    }

    public function cookie(Request $request, Response $response): Response
    {
        $cookieContent = '
            <div class="card bg-dark text-white border-secondary shadow-lg">
                <div class="card-body p-5">
                    <h1 class="mb-4 text-info"><i class="fa-solid fa-cookie-bite me-2"></i>Cookie Policy</h1>
                    <p class="lead text-white-50">Informativa estesa sull\'utilizzo dei Cookie e tecnologie similari.</p>
                    <hr class="my-4">
                    
                    <h4>1. Cosa sono i Cookie?</h4>
                    <p>I cookie sono piccoli file di testo che i siti visitati inviano al terminale dell\'utente, dove vengono memorizzati, per poi essere ritrasmessi agli stessi siti alla visita successiva.</p>
                    
                    <h4>2. Tipologie di Cookie utilizzate</h4>
                    <table class="table table-bordered table-dark mt-3 border-secondary">
                        <thead class="table-secondary text-dark">
                            <tr>
                                <th>Tipologia</th>
                                <th>Descrizione</th>
                                <th>Durata</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Cookie Tecnici (Sessione)</strong></td>
                                <td>Necessari per l\'autenticazione e la navigazione sicura (es. PHPSESSID).</td>
                                <td>Sessione</td>
                            </tr>
                            <tr>
                                <td><strong>Cookie di Sicurezza</strong></td>
                                <td>Utilizzati per prevenire attacchi CSRF e garantire l\'integrità.</td>
                                <td>Sessione</td>
                            </tr>
                            <tr>
                                <td><strong>Preferenze (LocalStorage)</strong></td>
                                <td>Salviamo la scelta sul banner dei cookie (chiave: <code>cookieConsented</code>).</td>
                                <td>Persistente</td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-success mt-4">
                        <i class="fa-solid fa-check-circle me-2"></i>
                        <strong>Nessun Tracciamento Pubblicitario</strong><br>
                        Questo sito NON utilizza cookie di profilazione o cookie di terze parti per finalità di marketing.
                    </div>
                    
                    <h4>3. Gestione del Consenso</h4>
                    <p>L\'utente può gestire le preferenze sui cookie direttamente dalle impostazioni del proprio browser o cliccando sul pulsante nel banner informativo.</p>
                </div>
            </div>
        ';

        $html = $this->engine->render('layout/layout', [
            'content' => $cookieContent,
            'title' => 'Cookie Policy - MCAG',
            'base_url' => (function () {
                $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
                return $scriptDir === '/' ? '' : $scriptDir;
            })()
        ]);
        $response->getBody()->write($html);
        return $response;
    }
}


