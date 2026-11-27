import 'vue-router'

declare module 'vue-router' {
  interface RouteMeta {
    title?: string
    /** Halaman dapat diakses tanpa login. */
    public?: boolean
    /** Permission backend yang dibutuhkan untuk membuka halaman. */
    permission?: string
    /** Hanya role Admin yang boleh membuka halaman. */
    adminOnly?: boolean
  }
}
