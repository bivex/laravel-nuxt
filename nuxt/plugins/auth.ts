export default defineNuxtPlugin(async (nuxtApp) => {
  const auth = useAuthStore();
  const config = useRuntimeConfig();

  if (config.public.authGuard === 'web' && import.meta.client) {
    await auth.fetchCsrf();
  }

  if (auth.logged) {
    await auth.fetchUser();
  }
})
