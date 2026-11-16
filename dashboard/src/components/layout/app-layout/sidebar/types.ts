import type { Component } from 'vue'

export type MenuType = 'main' | 'others'

export interface SubItem {
  name: string
  path: string
  pro?: boolean
  new?: boolean
  target?: string
}

export interface NavItem {
  name: string
  icon: Component
  path?: string
  new?: boolean
  target?: string
  disabled?: boolean
  /** Permission backend yang diperlukan agar menu ditampilkan. */
  permission?: string
  subItems?: SubItem[]
}
