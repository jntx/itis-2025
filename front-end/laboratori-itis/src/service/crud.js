import axios from "axios";

const apiClient = axios.create({
	baseURL: import.meta.env.VITE_APP_APP_URL,
	headers: {
		'Content-Type': 'application/json'
	}
});

export function retrieveAll(path, sessionId) {
	return apiClient.get(path, {
		headers: {
			Authorization: `${sessionId}`
		}
	});
}

export function getById(path, id, sessionId) {
	return apiClient.get(`${path}/${id}`, {
		headers: {
			Authorization: `${sessionId}`
		}
	});
}

export function create(path, data, sessionId) {
	return apiClient.post(path, data, {
		headers: {
			Authorization: `${sessionId}`
		}
	});
}

export function update(path, id, data, sessionId) {
	return apiClient.put(`${path}/${id}`, data, {
		headers: {
			Authorization: `${sessionId}`
		}
	});
}

export function remove(path, id, sessionId) {
	return apiClient.delete(`${path}/${id}`, {
		headers: {
			Authorization: `${sessionId}`
		}
	});
}

export function post(path, data, sessionId) {
	return apiClient.post(path, data, {
		headers: sessionId ? {
			Authorization: `${sessionId}`
		} : {}
	});
}

export function get(path, sessionId) {
	return apiClient.get(path, {
		headers: sessionId ? {
			Authorization: `${sessionId}`
		} : {}
	});
}
