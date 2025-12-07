<template>
  <div v-if="hasPages" class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">
    <div class="flex flex-1 justify-between sm:hidden">
      
        v-if="currentPage > 1"
        :href="getPreviousPageUrl()"
        class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Previous
      
        v-if="currentPage < lastPage"
        :href="getNextPageUrl()"
        class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
      >
        Next
    </div>
    
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Showing {{ from }} to {{ to }} of {{ total }} results
        </p>
      </div>
      <div>
        <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          
            v-if="currentPage > 1"
            :href="getPreviousPageUrl()"
            class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
          >
            <span class="sr-only">Previous</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
            </svg>
          
          <template v-for="page in visiblePages" :key="page">
            
              v-if="page !== currentPage"
              :href="getPageUrl(page)"
              class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
            >
              {{ page }}
            <span
              v-if="page === currentPage"
              aria-current="page"
              class="relative z-10 inline-flex items-center bg-indigo-600 px-4 py-2 text-sm font-semibold text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              {{ page }}
            </span>
          </template>
          
          
            v-if="currentPage < lastPage"
            :href="getNextPageUrl()"
            class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0"
          >
            <span class="sr-only">Next</span>
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
            </svg>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  links: {
    type: [Object, Array],
    default: () => ({})
  }
})

const currentPage = computed(() => {
  if (Array.isArray(props.links)) {
    const current = props.links.find(link => link.active)
    return current ? parseInt(current.label) : 1
  }
  return props.links.current_page || 1
})

const lastPage = computed(() => {
  if (Array.isArray(props.links)) {
    const pages = props.links.filter(link => !isNaN(parseInt(link.label)))
    return pages.length > 0 ? Math.max(...pages.map(p => parseInt(p.label))) : 1
  }
  return props.links.last_page || 1
})

const total = computed(() => {
  return props.links.total || 0
})

const from = computed(() => {
  return props.links.from || 0
})

const to = computed(() => {
  return props.links.to || 0
})

const hasPages = computed(() => {
  return lastPage.value > 1
})

const visiblePages = computed(() => {
  const pages = []
  const start = Math.max(1, currentPage.value - 2)
  const end = Math.min(lastPage.value, currentPage.value + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

const getPageUrl = (page) => {
  if (Array.isArray(props.links)) {
    const link = props.links.find(l => parseInt(l.label) === page)
    return link?.url || '#'
  }
  
  const url = new URL(window.location.href)
  url.searchParams.set('page', page)
  return url.toString()
}

const getPreviousPageUrl = () => {
  return getPageUrl(currentPage.value - 1)
}

const getNextPageUrl = () => {
  return getPageUrl(currentPage.value + 1)
}
</script>