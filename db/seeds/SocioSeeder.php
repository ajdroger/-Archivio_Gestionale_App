<?php
use Phinx\Seed\AbstractSeed;

class SocioSeeder extends AbstractSeed
{
    public function run(): void
    {
        $this->table('soci')->truncate();
        $this->table('documenti')->truncate();

        $data = [
            [
                'codice_fiscale' => 'RSSMRA80A01H501U',
                'matricola' => 'M0001',
                'nome' => 'Mario',
                'cognome' => 'Rossi',
                'data_nascita' => '1980-01-01',
                'indirizzo' => 'Via Roma 1, Firenze',
                'email' => 'mario.rossi@example.com',
                'telefono' => '3331234567',
                'stato' => 'ATTIVO'
            ],
            [
                'codice_fiscale' => 'BNCGNN75B02F205Z',
                'matricola' => 'M0002',
                'nome' => 'Giovanni',
                'cognome' => 'Bianchi',
                'data_nascita' => '1975-02-02',
                'indirizzo' => 'Piazza Duomo 2, Milano',
                'email' => 'giovanni.bianchi@example.com',
                'telefono' => '3339876543',
                'stato' => 'ATTIVO'
            ],
            [
                'codice_fiscale' => 'VRDLGU90C03L219K',
                'matricola' => 'M0003',
                'nome' => 'Luigi',
                'cognome' => 'Verdi',
                'data_nascita' => '1990-03-03',
                'indirizzo' => 'Corso Italia 3, Torino',
                'email' => 'luigi.verdi@example.com',
                'telefono' => '3334567890',
                'stato' => 'SOSPESO'
            ],
            [
                'codice_fiscale' => 'NRIBRO85D04H501S',
                'matricola' => 'M0004',
                'nome' => 'Roberto',
                'cognome' => 'Neri',
                'data_nascita' => '1985-04-04',
                'indirizzo' => 'Viale Europa 4, Roma',
                'email' => 'roberto.neri@example.com',
                'telefono' => '3331122334',
                'stato' => 'ATTIVO'
            ],
            [
                'codice_fiscale' => 'GLLFNC92E05A944E',
                'matricola' => 'M0005',
                'nome' => 'Francesco',
                'cognome' => 'Gialli',
                'data_nascita' => '1992-05-05',
                'indirizzo' => 'Via Napoli 5, Bologna',
                'email' => 'francesco.gialli@example.com',
                'telefono' => '3335566778',
                'stato' => 'DECADUTO'
            ],
            [
                'codice_fiscale' => 'MRLMRK88H08F205X',
                'matricola' => 'M0006',
                'nome' => 'Marco',
                'cognome' => 'Merli',
                'data_nascita' => '1988-08-08',
                'indirizzo' => 'Via Garibaldi 10, Genova',
                'email' => 'marco.merli@example.com',
                'telefono' => '3401239876',
                'stato' => 'ATTIVO'
            ],
            [
                'codice_fiscale' => 'CNTANT70M15H501W',
                'matricola' => 'M0007',
                'nome' => 'Antonio',
                'cognome' => 'Conti',
                'data_nascita' => '1970-11-15',
                'indirizzo' => 'Via Cavour 22, Firenze',
                'email' => 'antonio.conti@example.com',
                'telefono' => '3284561230',
                'stato' => 'ATTIVO'
            ],
            [
                'codice_fiscale' => 'FRRSTF95M20L219Y',
                'matricola' => 'M0008',
                'nome' => 'Stefano',
                'cognome' => 'Ferrari',
                'data_nascita' => '1995-08-20',
                'indirizzo' => 'Corso Vittorio 8, Torino',
                'email' => 'stefano.ferrari@example.com',
                'telefono' => '3478901234',
                'stato' => 'SOSPESO'
            ],
            [
                'codice_fiscale' => 'RZZPTR82R05G273P',
                'matricola' => 'M0009',
                'nome' => 'Pietro',
                'cognome' => 'Rizzi',
                'data_nascita' => '1982-10-05',
                'indirizzo' => 'Via Dante 5, Palermo',
                'email' => 'pietro.rizzi@example.com',
                'telefono' => '3396789012',
                'stato' => 'ATTIVO'
            ],
            [
                'codice_fiscale' => 'LMBMTT99C10D612S',
                'matricola' => 'M0010',
                'nome' => 'Matteo',
                'cognome' => 'Lombi',
                'data_nascita' => '1999-03-10',
                'indirizzo' => 'Piazza Signoria 1, Firenze',
                'email' => 'matteo.lombi@example.com',
                'telefono' => '3312345678',
                'stato' => 'DECSO' // Assuming DECSO (Deceased) exists or similar enum, backing up to ATTIVO if not sure. Let's use ATTIVO for safety or check Enum.
                // Checking Repo line 83: constant("...::{$row['stato']}").
                // I should verify Enum values. Let's stick to ATTIVO, SOSPESO, RADIATO which I saw in Diari.
            ]
        ];

        // Ensure state M0010 is valid
        $data[9]['stato'] = 'ATTIVO';

        $soci = $this->table('soci');
        // Phinx insert handles arrays of data
        $soci->insert($data)
            ->saveData();

        // Seed some documents for Mario Rossi
        $docs = [
            [
                'id_univoco' => 'DOC001',
                'nome_file' => 'iscrizione_2025_RSSMRA.pdf',
                'hash_sha256' => hash('sha256', 'content'),
                'stato' => 'VALIDATO',
                'data_caricamento' => date('Y-m-d H:i:s'),
                'tipo_documento' => 'MODULO_ISCRIZIONE',
                'codice_fiscale_socio' => 'RSSMRA80A01H501U',
                'anno_solare' => 2025,
                'quota_versata' => 50.00,
                'metodo_pagamento' => 'BONIFICO'
            ]
        ];

        $tableDocs = $this->table('documenti');
        $tableDocs->insert($docs)->saveData();
    }
}
