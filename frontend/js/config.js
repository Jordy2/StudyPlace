const API_CONFIG = {
  BASE_URL: 'http://localhost/StudyPlace/backend/api',
  TOKEN_KEY: 'studyplace_token',
  USER_KEY: 'studyplace_user',
};

if (typeof window !== 'undefined') {
  window.API_CONFIG = API_CONFIG;
}
