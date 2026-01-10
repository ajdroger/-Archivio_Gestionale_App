<?php

namespace FratellanzaMilitare\Service;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;

/**
 * Servizio Centralizzato di Validazione Input.
 * Wrapper intorno a Respect/Validation per garantire uniformità.
 */
class InputValidator
{
    /**
     * Valida un array di dati contro un set di regole.
     * 
     * @param array $data Dati da validare (es. $_POST)
     * @param array $rules Array associativo 'campo' => v::rule()
     * @return array Array degli errori (vuoto se valido)
     */
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $validator) {
            try {
                // Se il campo non esiste nei dati, verifica se la regola ammette null/optional
                // Respect/Validation di default fallisce se il valore manca, a meno che non si usi key()
                // Qui assumiamo validazione diretta del valore.
                $value = $data[$field] ?? null;
                $validator->assert($value);
            } catch (ValidationException $e) {
                // Ottieni messaggi o nesting
                $errors[$field] = $e->getMessages();
            }
        }

        return $errors;
    }

    /**
     * Esempio di regola predefinita per Codice Fiscale
     */
    public static function ruleCodiceFiscale()
    {
        return v::stringType()->length(16, 16)->regex('/^[A-Z0-9]+$/');
    }

    /**
     * Esempio di regola predefinita per Password Forte
     */
    public static function ruleStrongPassword()
    {
        return v::stringType()->length(8, null); // Todo: add complexity
    }
}
