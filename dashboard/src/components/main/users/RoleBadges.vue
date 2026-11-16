<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { roleColor, roleLabel } from '@/constants/roles'

defineProps<{ roles: string[] }>()
</script>

<template>
  <div class="flex flex-wrap gap-1.5">
    <Badge v-for="role in roles" :key="role" :color="roleColor(role)" size="sm">
      {{ roleLabel(role) }}
    </Badge>
    <Badge v-if="roles.length === 0" color="gray" size="sm">Tanpa role</Badge>
  </div>
</template>
