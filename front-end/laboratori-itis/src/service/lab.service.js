import * as CRUD from './crud';

export const labService = {
	retrieveAll(sessionId) {
		return CRUD.retrieveAll('/api/laboratori', sessionId);
	},
	
	getById(id, sessionId) {
		return CRUD.getById('/api/laboratori', id, sessionId);
	},
	
	create(laboratorio, sessionId) {
		return CRUD.create('/api/laboratori', laboratorio, sessionId);
	},
	
	update(id, laboratorio, sessionId) {
		return CRUD.update('/api/laboratori', id, laboratorio, sessionId);
	}
}
