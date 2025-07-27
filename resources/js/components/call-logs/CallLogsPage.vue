<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <Navigation />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-bold text-gray-900">Call Logs</h1>
          <p class="mt-2 text-sm text-gray-600">
            Track and analyze your voice assistant call history
          </p>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-8">
          <nav class="-mb-px flex space-x-8">
            <button
              @click="activeTab = 'history'"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === 'history'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Call History
            </button>
            <button
              @click="activeTab = 'analytics'"
              :class="[
                'py-2 px-1 border-b-2 font-medium text-sm',
                activeTab === 'analytics'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              Analytics
            </button>
          </nav>
        </div>

        <!-- Tab Content -->
        <div v-if="activeTab === 'history'">
          <CallLogsList 
            :assistants="assistants"
            :loading="loading"
            :call-logs="callLogs"
            :pagination="pagination"
            :filters="filters"
            @update-filters="updateFilters"
            @load-call-logs="loadCallLogs"
          />
        </div>

        <div v-else-if="activeTab === 'analytics'">
          <CallLogsStats 
            :stats="stats"
            :assistants="assistants"
            :loading="statsLoading"
            @update-filters="updateStatsFilters"
            @load-stats="loadStats"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import CallLogsList from './CallLogsList.vue'
import CallLogsStats from './CallLogsStats.vue'
import axios from 'axios'

export default {
  name: 'CallLogsPage',
  components: {
    Navigation,
    CallLogsList,
    CallLogsStats
  },
  data() {
    return {
      activeTab: 'history',
      loading: false,
      statsLoading: false,
      callLogs: [],
      pagination: null,
      stats: {
        totalCalls: 0,
        completedCalls: 0,
        failedCalls: 0,
        averageDuration: 0,
        totalCost: 0,
        inboundCalls: 0,
        outboundCalls: 0,
        statusBreakdown: {},
        assistantPerformance: []
      },
      assistants: [],
      filters: {
        page: 1,
        status: '',
        direction: '',
        assistant_id: '',
        start_date: '',
        end_date: '',
        search: ''
      },
      statsFilters: {
        start_date: '',
        end_date: '',
        assistant_id: ''
      }
    }
  },
  async mounted() {
    await this.loadAssistants()
    await this.loadCallLogs()
    await this.loadStats()
  },
  methods: {
    async loadAssistants() {
      try {
        const response = await axios.get('/api/assistants')
        this.assistants = response.data.data || []
      } catch (error) {
        // Handle error silently
      }
    },

    async loadCallLogs() {
      try {
        this.loading = true
        const params = { ...this.filters }
        
        const response = await axios.get('/api/call-logs', { params })
        this.callLogs = response.data.data || []
        this.pagination = response.data.meta || null
      } catch (error) {
        if (error.response && error.response.status === 401) {
          this.$router.push('/login')
        }
      } finally {
        this.loading = false
      }
    },

    async loadStats() {
      try {
        this.statsLoading = true
        const params = { ...this.statsFilters }
        
        const response = await axios.get('/api/call-logs/stats', { params })
        this.stats = response.data.data || this.stats
      } catch (error) {
        if (error.response && error.response.status === 401) {
          this.$router.push('/login')
        }
      } finally {
        this.statsLoading = false
      }
    },

    updateFilters(newFilters) {
      this.filters = { ...this.filters, ...newFilters, page: 1 }
      this.loadCallLogs()
    },

    updateStatsFilters(newFilters) {
      this.statsFilters = { ...this.statsFilters, ...newFilters }
      this.loadStats()
    }
  }
}
</script> 