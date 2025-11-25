<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Barangay Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="h-screen bg-cover bg-center"
      style="background-image: url('{{ asset('Barangay_background.jpg') }}'); background-position: center 30%;">

    <!-- Container with flex -->
    <div class="flex items-center justify-center h-full py-6 px-4">
        <!-- register box -->
        <div class="bg-white p-6 md:p-8 rounded-2xl shadow-lg w-full max-w-md bg-opacity-100 relative">
            
            <!-- Title -->
            <h2 class="text-xl font-bold text-center text-yellow-600 mb-4">CREATE YOUR ACCOUNT</h2>
            
            <!-- Error notification -->
            @if ($errors->any())
                <div class="mb-4 p-3 rounded border border-red-200 bg-red-50 text-red-700">
                    <strong>We couldn't complete your registration:</strong>
                    <ul class="list-disc ml-5 mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Form -->
            <form id="registrationForm" method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                @csrf

                <!-- Row 1: First Name and Last Name -->
                <div class="flex flex-row space-x-3 mb-3">
                    <!-- First Name -->
                    <div class="flex-1">
                        <input type="text" name="first_name" id="first_name" 
                            class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                            placeholder="First Name"
                            value="{{ old('first_name') }}" required>
                        @error('first_name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Last Name -->
                    <div class="flex-1">
                        <input type="text" name="last_name" id="last_name" 
                            class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                            placeholder="Last Name"
                            value="{{ old('last_name') }}" required>
                        @error('last_name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <!-- Row 2: Birthdate Dropdowns -->
                <div class="mb-3">
                    <div class="flex flex-row space-x-2">
                        <!-- Month -->
                        <div class="flex-1">
                            <select name="birth_month" id="birth_month" 
                                class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                                required>
                                <option value="" disabled selected>Month</option>
                                <option value="01">January</option>
                                <option value="02">February</option>
                                <option value="03">March</option>
                                <option value="04">April</option>
                                <option value="05">May</option>
                                <option value="06">June</option>
                                <option value="07">July</option>
                                <option value="08">August</option>
                                <option value="09">September</option>
                                <option value="10">October</option>
                                <option value="11">November</option>
                                <option value="12">December</option>
                            </select>
                        </div>
                        
                        <!-- Day -->
                        <div class="flex-1">
                            <select name="birth_day" id="birth_day" 
                                class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                                required>
                                <option value="" disabled selected>Day</option>
                                @for ($i = 1; $i <= 31; $i++)
                                    <option value="{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                        
                        <!-- Year -->
                        <div class="flex-1">
                            <select name="birth_year" id="birth_year" 
                                class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                                required>
                                <option value="" disabled selected>Year</option>
                                @for ($i = date('Y') - 1; $i >= date('Y') - 100; $i--)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    @error('birth_date')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Row 3: Gender Radio Buttons -->
                <div class="mb-3">
                    <div class="grid grid-cols-2 gap-3 w-full">
                        <label class="flex items-center justify-center border border-gray-300 rounded-md px-4 py-2 cursor-pointer hover:bg-gray-50 w-full">
                            <input type="radio" name="sex" value="Male" class="mr-2 text-yellow-500 focus:ring-yellow-500" {{ old('sex') == 'Male' ? 'checked' : '' }} required>
                            <span>Male</span>
                        </label>
                        <label class="flex items-center justify-center border border-gray-300 rounded-md px-4 py-2 cursor-pointer hover:bg-gray-50 w-full">
                            <input type="radio" name="sex" value="Female" class="mr-2 text-yellow-500 focus:ring-yellow-500" {{ old('sex') == 'Female' ? 'checked' : '' }} required>
                            <span>Female</span>
                        </label>
                    </div>
                    @error('sex')
                        <span class="text-red-500 text-xs mt-1 block text-center">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Row 4: Email -->
                <div class="mb-3">
                    <input type="email" name="email" id="email" 
                        class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent" 
                        placeholder="Email"
                        value="{{ old('email') }}" required>
                    <div id="emailStatusMessage" class="mt-1 text-xs font-medium hidden px-1"></div>
                    @error('email')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Row 5: Password -->
                <div class="mb-3">
                    <div class="relative">
                        <input type="password" name="password" id="password" 
                            class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent pr-10" 
                            placeholder="Password"
                            required>
                        <button type="button" id="togglePassword" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                            <i class="fas fa-eye-slash hidden" id="eyeOffIcon"></i>
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Row 6: Confirm Password -->
                <div class="mb-3">
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" 
                            class="w-full h-10 border border-gray-300 px-3 py-2 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent pr-10" 
                            placeholder="Confirm Password"
                            required>
                        <button type="button" id="togglePasswordConfirm" 
                            class="absolute right-3 top-1/2 -translate-y-1/2 p-1 rounded text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye" id="eyeIconConfirm"></i>
                            <i class="fas fa-eye-slash hidden" id="eyeOffIconConfirm"></i>
                        </button>
                    </div>
                </div>

                <!-- Row 7: ID Upload -->
                <div class="mb-3">
                    <div class="border-2 border-dashed border-gray-300 rounded-md p-4 text-center hover:bg-gray-50 transition cursor-pointer" id="upload-area">
                        <input type="file" name="id_image" id="id_image" 
                            class="hidden" accept="image/jpeg,image/png,image/jpg,image/gif">
                        <div id="upload-icon" class="mb-2">
                            <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl"></i>
                        </div>
                        <div id="upload-text">
                            <p class="text-sm text-gray-700">Upload ID Image</p>
                            <p class="text-xs text-gray-500">JPEG, PNG, JPG or GIF</p>
                        </div>
                        <div id="file-details" class="hidden mt-2 w-full text-left">
                            <div class="flex items-center p-2 bg-gray-50 rounded border border-gray-200">
                                <i class="fas fa-file-image text-yellow-500 mr-2"></i>
                                <span class="text-sm text-gray-700 truncate flex-1" id="file-name"></span>
                                <button type="button" id="remove-file" class="ml-auto text-gray-500 hover:text-red-500 p-1">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="fileErrorMessage" class="mt-1 text-xs font-medium hidden text-red-500"></div>
                    @error('id_image')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- PWD Status -->
                <div class="mb-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_pwd" id="is_pwd" value="1" 
                            class="w-4 h-4 text-yellow-500 border-gray-300 rounded focus:ring-yellow-500"
                            {{ old('is_pwd') ? 'checked' : '' }}>
                        <span class="ml-2 text-sm text-gray-700">I am a Person With Disability (PWD)</span>
                    </label>
                    @error('is_pwd')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- Form Buttons -->
                <div class="pt-4">
                    <button type="submit" class="w-full bg-yellow-500 text-white py-2 rounded-full font-bold hover:bg-yellow-600 transition">
                        REGISTER
                    </button>
                </div>
            </form>

            <p class="mt-6 text-center text-sm">Already have an account? 
                <a href="{{ route('login.form') }}" class="text-yellow-600 hover:underline font-semibold">Login</a>
            </p>
        </div>
    </div>

    <!-- Script for form interactions -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form fields
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirmation');
            const fileInput = document.getElementById('id_image');
            const birthMonth = document.getElementById('birth_month');
            const birthDay = document.getElementById('birth_day');
            const birthYear = document.getElementById('birth_year');
            
            // Status messages
            const emailStatusMessage = document.getElementById('emailStatusMessage');
            const fileErrorMessage = document.getElementById('fileErrorMessage');
            
            // Hidden birth date field
            const hiddenBirthDate = document.createElement('input');
            hiddenBirthDate.type = 'hidden';
            hiddenBirthDate.name = 'birth_date';
            document.getElementById('registrationForm').appendChild(hiddenBirthDate);
            
            // Update hidden birth date field when dropdowns change
            function updateBirthDate() {
                if (birthYear.value && birthMonth.value && birthDay.value) {
                    hiddenBirthDate.value = `${birthYear.value}-${birthMonth.value}-${birthDay.value}`;
                }
            }
            
            birthYear.addEventListener('change', updateBirthDate);
            birthMonth.addEventListener('change', updateBirthDate);
            birthDay.addEventListener('change', updateBirthDate);
            
            // Email validation
            email.addEventListener('blur', async function() {
                if (!this.value.trim()) return;
                
                // Basic email format validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    emailStatusMessage.textContent = 'Please enter a valid email address.';
                    emailStatusMessage.className = 'mt-1 text-xs font-medium text-red-600';
                    emailStatusMessage.classList.remove('hidden');
                    return;
                }
                
                try {
                    // Check if email exists
                    const response = await fetch('{{ route("check.email") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({ email: this.value })
                    });
                    
                    const data = await response.json();
                    
                    if (data.exists) {
                        // Email already exists
                        emailStatusMessage.textContent = 'This email is already in use.';
                        emailStatusMessage.className = 'mt-1 text-xs font-medium text-red-600';
                        emailStatusMessage.classList.remove('hidden');
                    } else {
                        // Email is available
                        emailStatusMessage.textContent = 'Email is available.';
                        emailStatusMessage.className = 'mt-1 text-xs font-medium text-green-600';
                        emailStatusMessage.classList.remove('hidden');
                    }
                } catch (error) {
                    console.error('Error checking email:', error);
                    emailStatusMessage.textContent = 'Error checking email. Please try again.';
                    emailStatusMessage.className = 'mt-1 text-xs font-medium text-red-600';
                    emailStatusMessage.classList.remove('hidden');
                }
            });
            
            // File upload validation
            fileInput.addEventListener('change', function() {
                if (!this.files || !this.files[0]) {
                    fileErrorMessage.textContent = 'Please select a file.';
                    fileErrorMessage.classList.remove('hidden');
                    return;
                }
                
                const file = this.files[0];
                const fileType = file.type;
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                
                if (!validTypes.includes(fileType)) {
                    fileErrorMessage.textContent = 'Please upload a valid image file (JPEG, PNG, JPG, or GIF).';
                    fileErrorMessage.classList.remove('hidden');
                    return;
                }
                
                // File is valid
                fileErrorMessage.classList.add('hidden');
                
                // Update UI
                const fileName = document.getElementById('file-name');
                const fileDetails = document.getElementById('file-details');
                const uploadIcon = document.getElementById('upload-icon');
                const uploadText = document.getElementById('upload-text');
                
                fileName.textContent = file.name;
                fileDetails.classList.remove('hidden');
                uploadIcon.classList.add('hidden');
                uploadText.classList.add('hidden');
            });
            
            // File upload area
            const uploadArea = document.getElementById('upload-area');
            const removeFile = document.getElementById('remove-file');
            
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            removeFile.addEventListener('click', function(e) {
                e.stopPropagation();
                fileInput.value = '';
                
                // Update UI
                const fileDetails = document.getElementById('file-details');
                const uploadIcon = document.getElementById('upload-icon');
                const uploadText = document.getElementById('upload-text');
                
                fileDetails.classList.add('hidden');
                uploadIcon.classList.remove('hidden');
                uploadText.classList.remove('hidden');
                fileErrorMessage.classList.add('hidden');
            });
            
            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, function() {
                    uploadArea.classList.add('border-yellow-500', 'bg-yellow-50');
                }, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, function() {
                    uploadArea.classList.remove('border-yellow-500', 'bg-yellow-50');
                }, false);
            });
            
            uploadArea.addEventListener('drop', function(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files && files.length) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, false);
            
            // Password visibility toggle
            const togglePassword = document.getElementById('togglePassword');
            const eyeIcon = document.getElementById('eyeIcon');
            const eyeOffIcon = document.getElementById('eyeOffIcon');
            
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                eyeIcon.classList.toggle('hidden');
                eyeOffIcon.classList.toggle('hidden');
            });
            
            const togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
            const eyeIconConfirm = document.getElementById('eyeIconConfirm');
            const eyeOffIconConfirm = document.getElementById('eyeOffIconConfirm');
            
            togglePasswordConfirm.addEventListener('click', function() {
                const type = passwordConfirm.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirm.setAttribute('type', type);
                eyeIconConfirm.classList.toggle('hidden');
                eyeOffIconConfirm.classList.toggle('hidden');
            });
            
            // Form submission validation
            document.getElementById('registrationForm').addEventListener('submit', function(e) {
                // Validate birth date
                if (!birthYear.value || !birthMonth.value || !birthDay.value) {
                    alert('Please select a complete birth date.');
                    e.preventDefault();
                    return;
                }
                
                // Validate password match
                if (password.value !== passwordConfirm.value) {
                    alert('Passwords do not match. Please try again.');
                    passwordConfirm.focus();
                    e.preventDefault();
                    return;
                }
                
               
                
                // Validate file type
                const file = fileInput.files[0];
                const fileType = file.type;
                const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
                
                if (!validTypes.includes(fileType)) {
                    alert('Please upload a valid image file (JPEG, PNG, JPG, or GIF).');
                    e.preventDefault();
                    return;
                }
            });
        });
    </script>
</body>
</html>