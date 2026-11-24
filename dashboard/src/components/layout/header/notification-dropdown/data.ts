import type { DashboardActivityApi } from '@/services/dashboard'
import type { NotificationItem } from './types'

/** Notifikasi berasal dari aktivitas nyata yang dikirim endpoint dashboard. */
export const notificationsFromActivities = (
  activities: DashboardActivityApi[],
): NotificationItem[] => {
  const timeFormatter = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })

  return activities.map((activity) => {
    const date = new Date(activity.time)

    return {
      id: activity.id,
      name: activity.user ?? 'Sistem',
      avatar: '',
      action: activity.title,
      project: activity.description,
      time: Number.isNaN(date.getTime()) ? '' : timeFormatter.format(date),
      status: 'online' as const,
    }
  })
}
