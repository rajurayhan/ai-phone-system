<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <Navigation />

    <div class="py-6">
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
                </div>

                <div class="mt-6">
                  <label for="bio" class="block text-sm font-medium text-gray-700">Bio</label>
                  <textarea
                    id="bio"
                    v-model="form.bio"
                    rows="4"
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                    placeholder="Tell us about yourself..."
                  ></textarea>
                </div>

                <div class="mt-6 flex justify-end">
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  >
                    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ loading ? 'Updating...' : 'Update Profile' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Change Password -->
          <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Change Password</h3>
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
                </div>

                <div class="mt-6">
                  <label for="new_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                  <input
                    id="new_password_confirmation"
                    v-model="passwordForm.new_password_confirmation"
                    type="password"
                    required
                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                  />
                </div>

                <div class="mt-6 flex justify-end">
                  <button
                    type="submit"
                    :disabled="passwordLoading"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                  >
                    <svg v-if="passwordLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
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
  </div>
</template>

<script>
import Navigation from '../shared/Navigation.vue'
import { showError, showSuccess } from '../../utils/sweetalert.js'
import axios from 'axios'

export default {
  name: 'Profile',
  components: {
    Navigation
  },
  data() {
    return {
      loading: false,
      passwordLoading: false,
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
      selectedFile: null
    }
  },
  computed: {
    userInitials() {
      return this.user.name ? this.user.name.split(' ').map(n => n[0]).join('').toUpperCase() : 'U';
    }
  },
  mounted() {
    this.fetchUserData();
  },
  methods: {
    async fetchUserData() {
      const token = localStorage.getItem('token');
      if (!token) {
        await showError('Authentication Error', 'No authentication token found. Please log in again.');
        this.$router.push('/login');
        return;
      }
      try {
        const response = await axios.get('/api/user', {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });
        
        if (response.data.success) {
          this.user = response.data.data;
          this.form = {
            name: this.user.name || '',
            email: this.user.email || '',
            phone: this.user.phone || '',
            company: this.user.company || '',
            bio: this.user.bio || ''
          };
          localStorage.setItem('user', JSON.stringify(this.user));
        } else {
          throw new Error('Failed to load user data');
        }
      } catch (error) {
        console.error('Error fetching user data:', error);
        await showError('Error', 'Failed to load user data. Please log in again.');
        this.$router.push('/login');
      }
    },
    handleProfilePictureChange(event) {
      const file = event.target.files[0];
      if (file) {
        if (file.size > 2 * 1024 * 1024) {
          showError('File Too Large', 'File size must be less than 2MB');
          return;
        }
        this.selectedFile = file;
      }
    },
    async updateProfile() {
      const token = localStorage.getItem('token');
      if (!token) {
        await showError('Authentication Error', 'No authentication token found. Please log in again.');
        this.$router.push('/login');
        return;
      }
      try {
        this.loading = true;
        
        // Create FormData for file upload
        const formData = new FormData();
        formData.append('name', this.form.name || '');
        formData.append('phone', this.form.phone || '');
        formData.append('company', this.form.company || '');
        formData.append('bio', this.form.bio || '');
        
        // Add profile picture if selected
        if (this.selectedFile) {
          formData.append('profile_picture', this.selectedFile);
        }
        

        
        const response = await axios.post('/api/user', formData, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'multipart/form-data'
          }
        });
        
        if (response.data.success) {
          // Update user data with response
          this.user = response.data.data;
          
          // Update form data to match the response
          this.form = {
            name: this.user.name || '',
            email: this.user.email || '',
            phone: this.user.phone || '',
            company: this.user.company || '',
            bio: this.user.bio || ''
          };
          
          localStorage.setItem('user', JSON.stringify(this.user));
          this.selectedFile = null;
          if (this.$refs.profilePictureInput) {
            this.$refs.profilePictureInput.value = '';
          }
          showSuccess('Profile Updated', 'Your profile has been updated successfully.');
        } else {
          throw new Error(response.data.message || 'Failed to update profile');
        }
      } catch (error) {
        console.error('Profile update error:', error);
        console.error('Error response:', error.response);
        let errorMessage = 'Failed to update profile.';
        if (error.response && error.response.data && error.response.data.message) {
          errorMessage = error.response.data.message;
        }
        await showError('Update Failed', errorMessage);
      } finally {
        this.loading = false;
      }
    },
    async changePassword() {
      if (this.passwordForm.new_password !== this.passwordForm.new_password_confirmation) {
        await showError('Password Mismatch', 'New passwords do not match');
        return;
      }
      const token = localStorage.getItem('token');
      if (!token) {
        await showError('Authentication Error', 'No authentication token found. Please log in again.');
        this.$router.push('/login');
        return;
      }
      try {
        this.passwordLoading = true;
        const response = await axios.put('/api/user/password', this.passwordForm, {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json'
          }
        });
        this.passwordForm = { current_password: '', new_password: '', new_password_confirmation: '' };
        showSuccess('Password Changed', 'Your password has been changed successfully.');
      } catch (error) {
        let errorMessage = 'Failed to change password.';
        if (error.response && error.response.data && error.response.data.message) {
          errorMessage = error.response.data.message;
        }
        await showError('Password Change Failed', errorMessage);
      } finally {
        this.passwordLoading = false;
      }
    }
  }
}
</script> 