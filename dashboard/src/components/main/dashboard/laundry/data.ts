import type { RevenuePeriodId } from './types'

/**
 * Metadata tampilan statis. Seluruh data operasional berasal dari endpoint
 * GET /api/v1/dashboard, bukan dari berkas ini.
 */
export const revenuePeriods: Array<{ id: RevenuePeriodId; label: string }> = [
  { id: '7d', label: '7 Hari' },
  { id: '30d', label: '30 Hari' },
  { id: 'month', label: 'Bulan Ini' },
]
