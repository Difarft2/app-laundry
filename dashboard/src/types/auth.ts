export type UserStatus = 'pending' | 'active' | 'rejected'

export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string[]
  permissions: string[]
  status: UserStatus
  created_at: string
  updated_at: string
}

export interface LoginPayload {
  email: string
  password: string
  device_name?: string
}

export interface LoginResult {
  access_token: string
  token_type: string
  user: AuthUser
}

export interface LoginResponse {
  success: boolean
  message: string
  data: LoginResult
}

export interface UserResponse {
  data: AuthUser
}

export interface ProfileResponse {
  success: boolean
  message: string
  data: {
    user: AuthUser
  }
}

export interface MessageResponse {
  success?: boolean
  message: string
}

export interface UpdateProfilePayload {
  name: string
  email: string
}

export interface UpdatePasswordPayload {
  current_password: string
  password: string
  password_confirmation: string
}

export interface UsersQuery {
  search?: string
  role?: string
  page?: number
}

export interface PaginationMeta {
  current_page: number
  from: number | null
  last_page: number
  per_page: number
  to: number | null
  total: number
}

export interface PaginationLinks {
  first: string | null
  last: string | null
  prev: string | null
  next: string | null
}

export interface PaginatedResponse<T> {
  data: T[]
  links: PaginationLinks
  meta: PaginationMeta
}
 
export interface PaginatedUsers {
  data: AuthUser[]
  links: PaginationLinks
  meta: PaginationMeta
}

export interface CreateUserPayload {
  name: string
  email: string
  no_hp?: string | null
  password: string
  password_confirmation: string
  roles: string[]
}

export interface UpdateUserPayload {
  name: string
  email: string
  no_hp?: string | null
  roles?: string[]
}

export interface ResetUserPasswordPayload {
  password: string
  password_confirmation: string
}

export interface UpdateUserStatusPayload {
  status: UserStatus
}

export interface UpdateUserStatusResponse {
  success?: boolean
  message: string
  data: AuthUser
}
