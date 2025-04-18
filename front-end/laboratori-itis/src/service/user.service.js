import * as CRUD from './crud';

export const userService = {
	getAllUsers(sessionId) {
		return CRUD.retrieveAll('/api/utenti', sessionId);
	}
}
