<template>
  <div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Navigation -->
    <Navigation />

    <div class="flex-1 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between">
          <div class="flex-1 min-w-0">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
              Profile Settings
            </h2>
            <p class="mt-1 text-sm text-gray-500">
              Manage your account settings and preferences
            </p>
          </div>
        </div>

        <div class="mt-8">
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <form @submit.prevent="updateProfile">
                <!-- Profile Picture -->
                <div class="mb-6">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                  <div class="flex items-center space-x-4">
                    <div class="flex-shrink-0">
                      <div v-if="user.profile_picture" class="h-20 w-20 rounded-full overflow-hidden">
                        <img :src="user.profile_picture" :alt="user.name" class="h-full w-full object-cover">
                      </div>
                      <div v-else class="h-20 w-20 rounded-full bg-green-600 flex items-center justify-center">
                        <span class="text-white text-xl font-medium">{{ userInitials }}</span>
                      </div>
                    </div>
                    <div class="flex-1">
                      <input
                        type="file"
                        ref="profilePictureInput"
                        @change="handleProfilePictureChange"
                        accept="image/*"
                        class="hidden"
                      />
                      <button
                        type="button"
                        @click="$refs.profilePictureInput.click()"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                      >
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Upload Photo
                      </button>
                      <p class="mt-1 text-sm text-gray-500">JPG, PNG or GIF up to 2MB</p>
                    </div>
                  </div>
                </div>

                <!-- Personal Information -->
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input
                      id="name"
                      v-model="form.name"
                      type="text"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input
                      id="email"
                      v-model="form.email"
                      type="email"
                      required
                      disabled
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 sm:text-sm"
                    />
                    <p class="mt-1 text-sm text-gray-500">Email cannot be changed</p>
                  </div>

                  <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <input
                      id="phone"
                      v-model="form.phone"
                      type="tel"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                    <input
                      id="company"
                      v-model="form.company"
                      type="text"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                    <textarea
                      id="bio"
                      v-model="form.bio"
                      rows="3"
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                      placeholder="Tell us about yourself..."
                    ></textarea>
                  </div>
                </div>

                <!-- Save Button -->
                <div class="mt-6 flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? 'Saving...' : 'Save Changes' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Change Password Section -->
          <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
              <form @submit.prevent="changePassword">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                  <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                    <input
                      id="current_password"
                      v-model="passwordForm.current_password"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                    <input
                      id="new_password"
                      v-model="passwordForm.new_password"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                    <input
                      id="new_password_confirmation"
                      v-model="passwordForm.new_password_confirmation"
                      type="password"
                      required
                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    />
                  </div>
                </div>

                <div class="mt-6 flex justify-end">
                  <button
                    type="submit"
                    :disabled="passwordLoading"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg v-if="passwordLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ passwordLoading ? 'Changing...' : 'Change Password' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import Footer from '../shared/Footer.vue'
import { updateDocumentTitle } from '../../utils/systemSettings.js'

export default {
  name: 'Profile',
  components: {
    Navigation,
    Footer
  },
  data() {
    return {
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
      loading: false,
      passwordLoading: false,
      selectedProfilePicture: null
    }
  },
  computed: {
    userInitials() {
      if (!this.user.name) return '';
      return this.user.name.split(' ').map(n => n[0]).join('').toUpperCase();
    }
  },
  async mounted() {
    await this.loadUser();
    await updateDocumentTitle('Profile');
  },
  methods: {
    async loadUser() {
      try {
        const user = JSON.parse(localStorage.getItem('user') || '{}');
        this.user = user;
        this.form = {
          name: user.name || '',
          email: user.email || '',
          phone: user.phone || '',
          company: user.company || '',
          bio: user.bio || ''
        };
      } catch (error) {
        console.error('Error loading user:', error);
      }
    },
    
    async updateProfile() {
      this.loading = true;
      try {
        const formData = new FormData();
        formData.append('name', this.form.name);
        formData.append('phone', this.form.phone || '');
        formData.append('company', this.form.company || '');
        
        if (this.selectedProfilePicture) {
          formData.append('profile_picture', this.selectedProfilePicture);
        }

        const response = await fetch('/api/profile/update', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
          },
          body: formData
        });

        if (response.ok) {
          const data = await response.json();
          // Update user in localStorage
          const user = JSON.parse(localStorage.getItem('user') || '{}');
          const updatedUser = { ...user, ...data.data };
          localStorage.setItem('user', JSON.stringify(updatedUser));
          
          this.user = updatedUser;
          this.$toast.success('Profile updated successfully');
        } else {
          const error = await response.json();
          this.$toast.error(error.message || 'Failed to update profile');
        }
      } catch (error) {
        console.error('Error updating profile:', error);
        this.$toast.error('An error occurred while updating profile');
      } finally {
        this.loading = false;
      }
    },
    
    handleProfilePictureChange(event) {
      const file = event.target.files[0];
      if (file) {
        this.selectedProfilePicture = file;
      }
    },
    
    async changePassword() {
      this.passwordLoading = true;
      try {
        const response = await fetch('/api/profile/change-password', {
          method: 'POST',
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(this.passwordForm)
        });

        if (response.ok) {
          this.$toast.success('Password changed successfully');
          this.passwordForm = {
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
          };
        } else {
          const error = await response.json();
          this.$toast.error(error.message || 'Failed to change password');
        }
      } catch (error) {
        console.error('Error changing password:', error);
        this.$toast.error('An error occurred while changing password');
      } finally {
        this.passwordLoading = false;
      }
    }
  }
}
</script> 