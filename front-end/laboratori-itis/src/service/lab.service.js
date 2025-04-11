import * as CRUD from './crud';

export const labService = {
	retrieveAll(sessionId) {
		return CRUD.retrieveAll('/api/laboratori', sessionId);
	}
}