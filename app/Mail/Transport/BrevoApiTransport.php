<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory as HttpFactory;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Envia los correos por la API HTTP de Brevo en lugar de SMTP.
 *
 * Motivos para no usar SMTP en produccion:
 *   - SMTP exige usuario y contrasena de buzon; la API solo una clave
 *     revocable desde el panel de Brevo, sin exponer la cuenta.
 *   - La salida por el puerto 587 desde Azure Container Apps es poco fiable
 *     y lenta de diagnosticar; HTTPS por el 443 siempre esta abierto y los
 *     errores vuelven como JSON legible.
 *
 * Se usa el cliente HTTP de Laravel (y no symfony/brevo-mailer) para no
 * sumar dependencias y poder probarlo con Http::fake().
 */
class BrevoApiTransport extends AbstractTransport
{
    private const ENDPOINT = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(
        private readonly HttpFactory $http,
        #[\SensitiveParameter] private readonly string $apiKey,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $respuesta = $this->http
            ->withHeaders(['api-key' => $this->apiKey])
            ->acceptJson()
            ->timeout(15)
            ->post(self::ENDPOINT, $this->payload($email, $message->getEnvelope()->getRecipients()));

        if ($respuesta->failed()) {
            throw new TransportException(sprintf(
                'Brevo rechazo el correo (HTTP %d): %s',
                $respuesta->status(),
                $respuesta->json('message') ?? $respuesta->body(),
            ));
        }

        if ($id = $respuesta->json('messageId')) {
            $message->setMessageId($id);
        }
    }

    /**
     * @param  Address[]  $destinatarios
     */
    private function payload(Email $email, array $destinatarios): array
    {
        $from = $email->getFrom()[0];
        $cc   = array_map(fn (Address $a) => $a->getAddress(), $email->getCc());
        $bcc  = array_map(fn (Address $a) => $a->getAddress(), $email->getBcc());

        // El sobre incluye cc y bcc; Brevo los quiere por separado.
        $to = array_filter(
            $destinatarios,
            fn (Address $a) => ! in_array($a->getAddress(), [...$cc, ...$bcc], true),
        );

        [$html, $adjuntos] = $this->adjuntos($email);

        return array_filter([
            'sender'      => $this->direccion($from),
            'to'          => array_values(array_map($this->direccion(...), $to)),
            'cc'          => array_map($this->direccion(...), $email->getCc()),
            'bcc'         => array_map($this->direccion(...), $email->getBcc()),
            'replyTo'     => ($r = $email->getReplyTo()[0] ?? null) ? $this->direccion($r) : null,
            'subject'     => $email->getSubject(),
            'htmlContent' => $html,
            'textContent' => $email->getTextBody(),
            'attachment'  => $adjuntos,
        ]);
    }

    /**
     * Brevo no acepta Content-ID en los adjuntos: una imagen en linea se
     * referencia por su nombre de archivo. Por eso se reescribe cada
     * "cid:<id aleatorio>" del HTML a "cid:<nombre>" (el QR del comprobante).
     *
     * @return array{0: ?string, 1: array<int, array{name: string, content: string}>}
     */
    private function adjuntos(Email $email): array
    {
        $html     = $email->getHtmlBody();
        $html     = is_resource($html) ? stream_get_contents($html) : $html;
        $adjuntos = [];

        foreach ($email->getAttachments() as $parte) {
            $nombre = $parte->getFilename() ?? 'adjunto';

            if ($html !== null && $parte->hasContentId()) {
                $html = str_replace('cid:'.$parte->getContentId(), 'cid:'.$nombre, $html);
            }

            $adjuntos[] = [
                'name'    => $nombre,
                'content' => base64_encode($parte->getBody()),
            ];
        }

        return [$html, $adjuntos];
    }

    private function direccion(Address $a): array
    {
        return array_filter(['email' => $a->getAddress(), 'name' => $a->getName()]);
    }

    public function __toString(): string
    {
        return 'brevo+api://api.brevo.com';
    }
}
