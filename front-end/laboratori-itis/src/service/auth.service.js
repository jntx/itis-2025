import * as CRUD from './crud';

export const authService = {
	login(credentials) {
		return CRUD.post('/api/login', credentials);
	},
	
	logout(sessionId) {
		return CRUD.get(`/api/logout/${sessionId}`);
	}
}
