Come utilizzarlo

1. Esegui lo script (Dry-Run):
   Per simulare le modifiche senza applicarle e vedere cosa verrebbe aggiornato:
   php tests/RenamerTool/SystemRenamer.php "nuovo-nome-cartella" --dry-run

2. Esegui la rinomina reale:
   Per applicare le modifiche ai file:
   php tests/RenamerTool/SystemRenamer.php "nuovo-nome-cartella"

3. Finalizza la rinomina della cartella Root:
   Poiché lo script gira all'interno della cartella stessa, non può rinominarla "al volo" senza rischi. 
   Lo script genererà automaticamente un file "rename_finalize.bat" nella root.
   
   - Chiudi eventuali editor o terminali aperti nella cartella.
   - Esegui "rename_finalize.bat" per completare l'operazione.
