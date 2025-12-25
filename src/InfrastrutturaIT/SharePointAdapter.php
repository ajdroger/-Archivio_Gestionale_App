<?php

namespace FratellanzaMilitare\InfrastrutturaIT;

class SharePointAdapter implements ICloudStorage
{
    /**
     * Carica un file su SharePoint
     * @param string $fileName Nome del file
     * @param string $content Contenuto del file
     * @return string URL del file caricato
     */
    public function upload(string $fileName, string $content): string
    {
        // Mock Implementation: Simula upload su SharePoint
        // In produzione: usare Microsoft Graph API

        $fileId = uniqid('sp_');
        $siteUrl = $this->siteUrl ?? "https://sharepoint.example.com";
        $url = "{$siteUrl}/Shared%20Documents/{$fileId}/{$fileName}";

        // Log dell'upload
        error_log("SharePoint Upload: {$fileName} ({$fileId})");

        return $url;
    }

    /**
     * Scarica un file da SharePoint
     * @param string $uuid UUID del file
     * @return string Contenuto del file
     */
    public function download(string $uuid): string
    {
        // Mock Implementation: Simula download da SharePoint
        // In produzione: usare Microsoft Graph API

        if (empty($uuid)) {
            throw new \InvalidArgumentException('UUID file non valido');
        }

        // Simulazione contenuto file
        $mockContent = "File content from SharePoint\n";
        $mockContent .= "UUID: {$uuid}\n";
        $mockContent .= "Downloaded: " . date('Y-m-d H:i:s') . "\n";

        error_log("SharePoint Download: {$uuid}");

        return $mockContent;
    }

    /**
     * Elimina un file da SharePoint
     * @param string $uuid UUID del file da eliminare
     * @return void
     */
    public function delete(string $uuid): void
    {
        // Mock Implementation: Simula eliminazione da SharePoint
        // In produzione: usare Microsoft Graph API

        if (empty($uuid)) {
            throw new \InvalidArgumentException('UUID file non valido');
        }

        error_log("SharePoint Delete: {$uuid}");

        // Simula successo (in produzione: chiamata API reale)
    }
}
