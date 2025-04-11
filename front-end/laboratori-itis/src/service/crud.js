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