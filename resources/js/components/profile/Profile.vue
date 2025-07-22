<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <router-link to="/dashboard" class="flex items-center">
                <div class="h-8 w-8 bg-gradient-to-r from-primary-600 to-blue-600 rounded-lg flex items-center justify-center">
                  <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                  </svg>
                </div>
                <div class="ml-2">
                  <h1 class="text-xl font-bold text-gray-900">LHG AI Voice Agent</h1>
                </div>
              </router-link>
            </div>
          </div>
          <div class="flex items-center">
            <div class="ml-3 relative">
              <div>
                <button @click="userMenuOpen = !userMenuOpen" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  <span class="sr-only">Open user menu</span>
                  <div class="h-8 w-8 rounded-full bg-primary-500 flex items-center justify-center">
                    <span class="text-white font-medium">{{ userInitials }}</span>
                  </div>
                </button>
              </div>
              <div v-if="userMenuOpen" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                <router-link to="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your Profile</router-link>
                <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
          <!-- Profile Header -->
          <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <div class="h-16 w-16 rounded-full bg-primary-500 flex items-center justify-center">
                    <span class="text-white text-xl font-medium">{{ userInitials }}</span>
                  </div>
                </div>
                <div class="ml-6">
                  <h1 class="text-2xl font-bold text-gray-900">{{ user.name }}</h1>
                  <p class="text-sm text-gray-500">{{ user.email }}</p>
                  <p class="text-sm text-gray-500">Member since {{ user.created_at }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Profile Form -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Profile Information</h3>
              
              <form @submit.prevent="updateProfile">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.name }"
                    />
                    <p v-if="errors.name" class="mt-2 text-sm text-red-600">{{ errors.name }}</p>
                  </div>

                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.email }"
                    />
                    <p v-if="errors.email" class="mt-2 text-sm text-red-600">{{ errors.email }}</p>
                  </div>

                  <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <input
                      id="phone"
                      v-model="form.phone"
                      type="tel"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.phone }"
                    />
                    <p v-if="errors.phone" class="mt-2 text-sm text-red-600">{{ errors.phone }}</p>
                  </div>

                  <div>
                    <label for="company" class="block text-sm font-medium text-gray-700 mb-2">Company</label>
                    <input
                      id="company"
                      v-model="form.company"
                      type="text"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.company }"
                    />
                    <p v-if="errors.company" class="mt-2 text-sm text-red-600">{{ errors.company }}</p>
                  </div>

                  <div class="sm:col-span-2">
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">Bio</label>
                    <textarea
                      id="bio"
                      v-model="form.bio"
                      rows="4"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200 resize-none"
                      :class="{ 'border-red-500 focus:ring-red-500': errors.bio }"
                    ></textarea>
                    <p v-if="errors.bio" class="mt-2 text-sm text-red-600">{{ errors.bio }}</p>
                  </div>
                </div>

                <div class="mt-6">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="w-full sm:w-auto px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors duration-200 disabled:opacity-50"
                  >
                    {{ loading ? 'Updating...' : 'Update Profile' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Change Password -->
          <div class="bg-white shadow rounded-lg mt-6">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-6">Change Password</h3>
              
              <form @submit.prevent="changePassword">
                <div class="space-y-4">
                  <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">Current Password</label>
                    <input
                      id="current_password"
                      v-model="passwordForm.current_password"
                      type="password"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': passwordErrors.current_password }"
                    />
                    <p v-if="passwordErrors.current_password" class="mt-2 text-sm text-red-600">{{ passwordErrors.current_password }}</p>
                  </div>

                  <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input
                      id="new_password"
                      v-model="passwordForm.new_password"
                      type="password"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': passwordErrors.new_password }"
                    />
                    <p v-if="passwordErrors.new_password" class="mt-2 text-sm text-red-600">{{ passwordErrors.new_password }}</p>
                  </div>

                  <div>
                    <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm New Password</label>
                    <input
                      id="new_password_confirmation"
                      v-model="passwordForm.new_password_confirmation"
                      type="password"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors duration-200"
                      :class="{ 'border-red-500 focus:ring-red-500': passwordErrors.new_password_confirmation }"
                    />
                    <p v-if="passwordErrors.new_password_confirmation" class="mt-2 text-sm text-red-600">{{ passwordErrors.new_password_confirmation }}</p>
                  </div>
                </div>

                <div class="mt-6">
                  <button
                    type="submit"
                    :disabled="passwordLoading"
                    class="w-full sm:w-auto px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg transition-colors duration-200 disabled:opacity-50"
                  >
                    {{ passwordLoading ? 'Changing...' : 'Change Password' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Success/Error Messages -->
          <div v-if="successMessage" class="mt-6 bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-green-800">{{ successMessage }}</p>
              </div>
            </div>
          </div>

          <div v-if="errorMessage" class="mt-6 bg-red-50 border border-red-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <p class="text-sm text-red-800">{{ errorMessage }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Profile',
  data() {
    return {
      userMenuOpen: false,
      user: {},
      form: {
        name: '',
        email: '',
        phone: '',
        company: '',
        bio: ''
      },
      passwordForm: {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
      },
      errors: {},
      passwordErrors: {},
      loading: false,
      passwordLoading: false,
      successMessage: '',
      errorMessage: ''
    }
  },
  computed: {
    userInitials() {
      return this.user.name ? this.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    }
  },
  async mounted() {
    await this.loadUserProfile();
  },
  methods: {
    async loadUserProfile() {
      try {
        const response = await axios.get('/api/user');
        this.user = response.data;
        this.form = {
          name: this.user.name || '',
          email: this.user.email || '',
          phone: this.user.phone || '',
          company: this.user.company || '',
          bio: this.user.bio || ''
        };
      } catch (error) {
        console.error('Error loading user profile:', error);
      }
    },
    async updateProfile() {
      this.loading = true;
      this.errors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        const response = await axios.put('/api/user', this.form);
        this.user = response.data;
        this.successMessage = 'Profile updated successfully!';
      } catch (error) {
        if (error.response && error.response.data.errors) {
          this.errors = error.response.data.errors;
        } else {
          this.errorMessage = 'An error occurred while updating your profile.';
        }
      } finally {
        this.loading = false;
      }
    },
    async changePassword() {
      this.passwordLoading = true;
      this.passwordErrors = {};
      this.successMessage = '';
      this.errorMessage = '';

      try {
        await axios.put('/api/user/password', this.passwordForm);
        this.passwordForm = {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        };
        this.successMessage = 'Password changed successfully!';
      } catch (error) {
        if (error.response && error.response.data.errors) {
          this.passwordErrors = error.response.data.errors;
        } else {
          this.errorMessage = 'An error occurred while changing your password.';
        }
      } finally {
        this.passwordLoading = false;
      }
    },
    logout() {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      this.$router.push('/login');
    }
  }
}
</script> 