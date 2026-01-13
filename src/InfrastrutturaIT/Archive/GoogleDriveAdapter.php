<?php

namespace MCAG\InfrastrutturaIT;

class GoogleDriveAdapter implements ICloudStorage
{
    public function scan(string $path = ''): void
    {
        // Implementazione
    }

    public function upload(string $fileName, string $content): string
    {
        // Implementazione Mock: Simula il ritardo dell'upload e ritorna un URL fittizio
        // Nel mondo reale: Client->files->create($fileName, $content)

        $fakeId = uniqid('gdrive_');
        return "https://drive.google.com/file/d/{$fakeId}/view?file=" . urlencode($fileName);
    }

    public function download(string $uuid)
    {
        // Implementazione Mock: Ritorna un contenuto di esempio
        return "File content for {$uuid} from Google Drive";
    }

    public function delete(string $uuid): void
    {
        // Implementazione Mock: Non fa nulla
        // echo "Eseguita eliminazione di {$uuid} da Google Drive";
    }
}


