import * as CRUD from './crud';

export const prenotazioneService = {
	getByLaboratorioId(laboratorioId, sessionId) {
		return CRUD.getById('/api/prenotazioni', laboratorioId, sessionId);
	},
	
	create(prenotazione, sessionId) {
		return CRUD.create('/api/prenotazioni', prenotazione, sessionId);
	}
}
