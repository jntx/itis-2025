# PROGETTO ITIS 2025

Backend realizzato con Slim Framework ( https://www.slimframework.com/  - vedi anche README.md in directory back-end).  
Comando di avvio del backend (da root backend): `php -S localhost:8000 -t public`  

## Progetto prenotazione laboratori
Ecco una possibile struttura delle tabelle in MySQL per la web app di prenotazione dei laboratori:

---

### **Tabella `utenti`**
Questa tabella gestisce le informazioni degli utenti (studenti e amministratori):

- **id** (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco dell'utente.
- **username** (VARCHAR(50)): Nome utente per il login.
- **password** (VARCHAR(255)): Password (meglio se memorizzata in forma hash).
- **email** (VARCHAR(100)): Email dell'utente.
- **ruolo** (ENUM('studente','admin')): Indica il tipo di utente (es. studente o amministratore).
- **created_at** (TIMESTAMP): Data di creazione dell'account.

---

### **Tabella `sessioni`**
Questa tabella gestisce le sessioni attive degli utenti (studenti e amministratori):

- **id** (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco del record.
- **username** (VARCHAR(50)): Nome utente.
- **sessione_id** (VARCHAR(50)): Hash della sessione.
- **laste_seen** (DATETIME): Data ora dell'ultima attività fatta dall'utente.

---

### **Tabella `laboratori`**
Questa tabella contiene le informazioni relative ai laboratori disponibili per le prenotazioni:

- **id** (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco del laboratorio.
- **nome** (VARCHAR(100)): Nome del laboratorio.
- **descrizione** (TEXT): Descrizione e dettagli del laboratorio.
- **capacita** (INT): Numero massimo di prenotazioni/partecipanti per slot o per giorno.
- **orario_apertura** (TIME): Ora di inizio della disponibilità.
- **orario_chiusura** (TIME): Ora di fine della disponibilità.
- **data_inizio** (DATE): Data a partire dalla quale il laboratorio è disponibile.
- **data_fine** (DATE): Data fino alla quale il laboratorio è disponibile (se applicabile).

*Nota:* I campi relativi agli orari e date possono essere adattati in base alle necessità dell'applicazione (ad esempio, se la disponibilità è a slot orari specifici, si potrebbe creare una tabella separata per gestirli).

---

### **Tabella `prenotazioni`**
Questa tabella registra le prenotazioni effettuate dagli utenti:

- **id** (INT, PRIMARY KEY, AUTO_INCREMENT): Identificatore univoco della prenotazione.
- **utente_id** (INT): Chiave esterna che fa riferimento all'id nella tabella `utenti`.
- **laboratorio_id** (INT): Chiave esterna che fa riferimento all'id nella tabella `laboratori`.
- **data_prenotazione** (DATE): La data per la quale è stata effettuata la prenotazione.
- **ora_inizio** (TIME): Orario di inizio della prenotazione (se previsto).
- **ora_fine** (TIME): Orario di fine della prenotazione (se previsto).
- **stato** (ENUM('confermata', 'in attesa', 'annullata')): Stato della prenotazione.

*Nota:* È possibile aggiungere ulteriori campi come eventuali note, timestamp di creazione/modifica o sistemi di notifica in base alle necessità.

---

Questa struttura fornisce un punto di partenza solido per gestire utenti, laboratori e prenotazioni in maniera organizzata e scalabile. Naturalmente, puoi adattare i campi e i tipi di dati in base alle esigenze specifiche del progetto.

```sql
-- Tabella per gli utenti
CREATE TABLE utenti (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    ruolo ENUM('studente', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabella per i laboratori
CREATE TABLE laboratori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descrizione TEXT,
    capacita INT NOT NULL,
    orario_apertura TIME,
    orario_chiusura TIME,
    data_inizio DATE,
    data_fine DATE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabella per le prenotazioni
CREATE TABLE prenotazioni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utente_id INT NOT NULL,
    laboratorio_id INT NOT NULL,
    data_prenotazione DATE NOT NULL,
    ora_inizio TIME,
    ora_fine TIME,
    stato ENUM('confermata', 'in attesa', 'annullata') NOT NULL DEFAULT 'in attesa',
    FOREIGN KEY (utente_id) REFERENCES utenti(id) ON DELETE CASCADE,
    FOREIGN KEY (laboratorio_id) REFERENCES laboratori(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE sessioni (
	id int AUTO_INCREMENT PRIMARY KEY,
	sessione_id varchar(50) NOT NULL,
	username varchar(50) NOT NULL,
	last_seen datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```


Le colonne **data_inizio** e **data_fine** nella tabella `laboratori` servono a definire il periodo di disponibilità del laboratorio per le prenotazioni. In pratica:

- **data_inizio:** Indica la data a partire dalla quale il laboratorio è disponibile per essere prenotato.
- **data_fine:** Specifica la data fino alla quale il laboratorio resta disponibile per le prenotazioni.

Queste informazioni sono utili per gestire dinamicamente la disponibilità dei laboratori, ad esempio in caso di periodi di manutenzione, festività o per limitare la prenotazione a determinati periodi dell'anno.