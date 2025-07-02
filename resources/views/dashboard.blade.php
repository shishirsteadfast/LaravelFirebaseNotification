<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex items-center justify-center">
            <div class="bg-white shadow-lg sm:rounded-lg p-4 max-w-md w-full">
                <!-- Alpine component -->
                <form x-data="notificationForm()" @submit.prevent="sendNotification" method="POST">
                    @csrf

                    <!-- Title input -->
                    <div>
                        <x-input-label for="title" value="{{ __('Title') }}" class="sr-only" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                            placeholder="{{ __('Title') }}" x-model="title" required autofocus />
                    </div>

                    <!-- Message textarea -->
                    <div class="mt-4">
                        <x-input-label for="message" value="{{ __('Message') }}" class="sr-only" />
                        <textarea id="message" name="message"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm "
                            placeholder="{{ __('Message') }}" x-model="message" required></textarea>
                    </div>

                    <!-- Submit button -->
                    <div class="flex items-center justify-end mt-4">
                        <button type="submit" :disabled="isLoading"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded disabled:opacity-50 flex items-center">
                            <svg x-show="isLoading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2
                                         5.291A7.962 7.962 0 014 12H0c0 3.042 1.135
                                         5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span x-show="!isLoading">Send</span>
                            <span x-show="isLoading">Sending...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Alpine.js component definition -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var toastMixin = Swal.mixin({
            toast: true,
            position: 'bottom-right',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    </script>
    <script>
        function notificationForm() {
            return {
                title: '',
                message: '',
                isLoading: false,
                sendNotification() {
                    this.isLoading = true;

                    axios.post("{{ route('firebase.notification') }}", {
                            title: this.title,
                            message: this.message,
                            _token: '{{ csrf_token() }}'
                        })
                        .then(response => {
                            this.title = '';
                            this.message = '';
                            this.isLoading = false;
                        })
                        .catch(error => {
                            this.isLoading = false;
                            toastMixin.fire({
                                icon: 'error',
                                title: error.response.data.message
                            });
                        });
                }
            }
        }
    </script>
</x-app-layout>
