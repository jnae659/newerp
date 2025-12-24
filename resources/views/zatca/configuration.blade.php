<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZATCA Configuration - Saudi Arabia</title>
    <style>
        .zatca-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .zatca-header {
            background: linear-gradient(135deg, #006633 0%, #004d26 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .zatca-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .zatca-card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }
        .zatca-card-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-control:focus {
            border-color: #006633;
            box-shadow: 0 0 0 0.2rem rgba(0, 102, 51, 0.25);
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        .btn-primary {
            background: #006633;
            color: white;
        }
        .btn-primary:hover {
            background: #004d26;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .phase-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        .phase-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #6c757d;
            border-bottom: 3px solid transparent;
        }
        .phase-tab.active {
            color: #006633;
            border-bottom-color: #006633;
        }
        .zatca-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-enabled {
            background: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background: #f8d7da;
            color: #721c24;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #006633;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .requirements-list {
            list-style: none;
            padding: 0;
        }
        .requirements-list li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .requirements-list li:before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="zatca-container">
        <!-- Header -->
        <div class="zatca-header">
            <h1>ZATCA Integration - Saudi Arabia</h1>
            <p>Configure ZATCA (Zakat, Tax and Customs Authority) integration for Phase 1 & Phase 2 compliance</p>
            <div class="zatca-status {{ $zatcaConfig && $zatcaConfig->zatca_enabled === 'on' ? 'status-enabled' : 'status-disabled' }}">
                {{ $zatcaConfig && $zatcaConfig->zatca_enabled === 'on' ? 'Enabled' : 'Disabled' }}
            </div>
        </div>

        <!-- Messages -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form id="zatcaConfigurationForm" method="POST" action="{{ route('zatca.configuration.update') }}">
            @csrf
            
            <!-- Configuration Card -->
            <div class="zatca-card">
                <div class="zatca-card-header">
                    Basic Configuration
                </div>
                <div class="zatca-card-body">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="zatca_enabled" value="on" 
                                   {{ $zatcaConfig && $zatcaConfig->zatca_enabled === 'on' ? 'checked' : '' }}>
                            Enable ZATCA Integration
                        </label>
                        <small class="form-text text-muted">
                            Enable ZATCA integration for Saudi Arabia compliance
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">ZATCA Phase</label>
                        <div class="phase-tabs">
                            <button type="button" class="phase-tab {{ ($zatcaConfig && $zatcaConfig->zatca_phase === 'phase1') || !$zatcaConfig ? 'active' : '' }}" 
                                    onclick="selectPhase('phase1')">Phase 1 - Basic Tax Compliance</button>
                            <button type="button" class="phase-tab {{ $zatcaConfig && $zatcaConfig->zatca_phase === 'phase2' ? 'active' : '' }}" 
                                    onclick="selectPhase('phase2')">Phase 2 - Advanced E-Invoicing</button>
                        </div>
                        <input type="hidden" name="zatca_phase" id="zatca_phase" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_phase : 'phase1' }}">
                        <small class="form-text text-muted">
                            Phase 1: Basic tax reporting. Phase 2: Full e-invoicing with digital signatures
                        </small>
                    </div>
                </div>
            </div>

            <!-- Company Information Card -->
            <div class="zatca-card">
                <div class="zatca-card-header">
                    Company Information
                </div>
                <div class="zatca-card-body">
                    <div class="form-group">
                        <label class="form-label" for="zatca_tax_number">Tax Number *</label>
                        <input type="text" class="form-control" id="zatca_tax_number" name="zatca_tax_number" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_tax_number : '' }}" 
                               placeholder="15-digit Saudi Tax Number" maxlength="15">
                        <small class="form-text text-muted">
                            Your 15-digit Saudi Tax Number (required for both phases)
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zatca_branch_code">Branch Code *</label>
                        <input type="text" class="form-control" id="zatca_branch_code" name="zatca_branch_code" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_branch_code : '' }}" 
                               placeholder="3-digit Branch Code" maxlength="3">
                        <small class="form-text text-muted">
                            Your 3-digit branch code (required for both phases)
                        </small>
                    </div>
                </div>
            </div>

            <!-- API Configuration Card -->
            <div class="zatca-card" id="apiConfigCard" style="display: {{ ($zatcaConfig && $zatcaConfig->zatca_phase === 'phase2') ? 'none' : 'block' }}">
                <div class="zatca-card-header">
                    API Configuration (Phase 1)
                </div>
                <div class="zatca-card-body">
                    <div class="form-group">
                        <label class="form-label" for="zatca_api_endpoint">API Endpoint *</label>
                        <input type="url" class="form-control" id="zatca_api_endpoint" name="zatca_api_endpoint" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_api_endpoint : '' }}" 
                               placeholder="https://api.zatca.gov.sa/v1">
                        <small class="form-text text-muted">
                            ZATCA API endpoint for Phase 1
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zatca_api_key">API Key *</label>
                        <input type="text" class="form-control" id="zatca_api_key" name="zatca_api_key" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_api_key : '' }}" 
                               placeholder="Your ZATCA API Key">
                        <small class="form-text text-muted">
                            Your ZATCA API Key
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zatca_api_secret">API Secret *</label>
                        <input type="password" class="form-control" id="zatca_api_secret" name="zatca_api_secret" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_api_secret : '' }}" 
                               placeholder="Your ZATCA API Secret">
                        <small class="form-text text-muted">
                            Your ZATCA API Secret
                        </small>
                    </div>
                </div>
            </div>

            <!-- Phase 2 Configuration Card -->
            <div class="zatca-card" id="phase2ConfigCard" style="display: {{ $zatcaConfig && $zatcaConfig->zatca_phase === 'phase2' ? 'block' : 'none' }}">
                <div class="zatca-card-header">
                    Phase 2 - E-Invoicing Configuration
                </div>
                <div class="zatca-card-body">
                    <div class="form-group">
                        <label class="form-label" for="zatca_device_id">Device ID *</label>
                        <input type="text" class="form-control" id="zatca_device_id" name="zatca_device_id" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_device_id : '' }}" 
                               placeholder="6-digit Device ID">
                        <small class="form-text text-muted">
                            Your 6-digit device ID for Phase 2 e-invoicing
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zatca_certificate_path">Certificate Path *</label>
                        <input type="text" class="form-control" id="zatca_certificate_path" name="zatca_certificate_path" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_certificate_path : '' }}" 
                               placeholder="path/to/certificate.pem">
                        <small class="form-text text-muted">
                            Path to your digital certificate file
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="zatca_private_key_path">Private Key Path *</label>
                        <input type="text" class="form-control" id="zatca_private_key_path" name="zatca_private_key_path" 
                               value="{{ $zatcaConfig ? $zatcaConfig->zatca_private_key_path : '' }}" 
                               placeholder="path/to/private_key.pem">
                        <small class="form-text text-muted">
                            Path to your private key file
                        </small>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="zatca-card">
                <div class="zatca-card-body">
                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                    <button type="button" class="btn btn-info" onclick="testConnection()">Test Connection</button>
                    <button type="button" class="btn btn-success" onclick="validateConfiguration()">Validate Configuration</button>
                    
                    <div class="loading" id="loadingIndicator">
                        <div class="spinner"></div>
                        <p>Processing...</p>
                    </div>
                </div>
            </div>
        </form>

        <!-- Requirements Card -->
        <div class="zatca-card">
            <div class="zatca-card-header">
                Phase Requirements
            </div>
            <div class="zatca-card-body">
                <h5>Phase 1 Requirements:</h5>
                <ul class="requirements-list">
                    <li>Tax Number (15 digits)</li>
                    <li>Branch Code (3 digits)</li>
                    <li>API Endpoint</li>
                    <li>API Key and Secret</li>
                    <li>Basic tax reporting capabilities</li>
                </ul>

                <h5>Phase 2 Requirements:</h5>
                <ul class="requirements-list">
                    <li>All Phase 1 requirements</li>
                    <li>Device ID (6 digits)</li>
                    <li>Digital Certificate</li>
                    <li>Private Key</li>
                    <li>QR Code generation</li>
                    <li>Digital signatures</li>
                    <li>Real-time reporting</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function selectPhase(phase) {
            // Update tab states
            document.querySelectorAll('.phase-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Update hidden input
            document.getElementById('zatca_phase').value = phase;
            
            // Show/hide configuration cards
            const apiConfigCard = document.getElementById('apiConfigCard');
            const phase2ConfigCard = document.getElementById('phase2ConfigCard');
            
            if (phase === 'phase1') {
                apiConfigCard.style.display = 'block';
                phase2ConfigCard.style.display = 'none';
            } else {
                apiConfigCard.style.display = 'none';
                phase2ConfigCard.style.display = 'block';
            }
        }

        function testConnection() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            loadingIndicator.style.display = 'block';
            
            fetch('{{ route("zatca.test-connection") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                alert(data.success ? 'Connection successful!' : 'Connection failed: ' + (data.error || 'Unknown error'));
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                alert('Connection test failed: ' + error.message);
            });
        }

        function validateConfiguration() {
            const loadingIndicator = document.getElementById('loadingIndicator');
            loadingIndicator.style.display = 'block';
            
            fetch('{{ route("zatca.validate-configuration") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                loadingIndicator.style.display = 'none';
                if (data.success) {
                    alert('Configuration is valid!');
                } else {
                    alert('Configuration errors:\n' + data.errors.join('\n'));
                }
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                alert('Validation failed: ' + error.message);
            });
        }
    </script>
</body>
</html>
