@php
    SeoHelper::setTitle('Upload Files - GenZ NewZ');
    SeoHelper::setDescription('Upload your files to share with GenZ NewZ.');
@endphp

<section class="upload-page">
    <div class="upload-container">
        {{-- Header --}}
        <div class="upload-header">
            <h1 class="upload-title">{{ __('Upload Files') }}</h1>
            <p class="upload-subtitle">{{ __('Share your photos, videos, and documents') }}</p>
            <div class="header-divider"></div>
        </div>

        {{-- Upload Section --}}
        <div class="upload-box">
            <div id="dropZone" class="drop-zone">
                <div class="drop-zone-content">
                    <span class="drop-icon">📁</span>
                    <p class="drop-text">{{ __('Drop files here or click to browse') }}</p>
                    <p class="drop-formats">{{ __('Images, Videos, PDFs, Documents up to 100MB') }}</p>
                </div>
                <input type="file" id="fileInput" multiple hidden 
                       accept="image/*,video/*,.pdf,.doc,.docx,.txt">
            </div>

            {{-- Upload Progress --}}
            <div id="uploadProgress" class="upload-progress" style="display: none;">
                <div class="progress-header">
                    <span class="progress-file">Uploading...</span>
                    <span class="progress-percent">0%</span>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar"></div>
                </div>
                <div class="progress-status"></div>
            </div>

            {{-- Uploaded Files List --}}
            <div id="uploadedFiles" class="uploaded-files"></div>
        </div>

        {{-- Info Sidebar --}}
        <aside class="upload-sidebar">
            <div class="info-box">
                <h3>{{ __('What You Can Upload') }}</h3>
                <ul class="info-list">
                    <li>{{ __('Photos & Images') }}</li>
                    <li>{{ __('Video Files') }}</li>
                    <li>{{ __('PDF Documents') }}</li>
                    <li>{{ __('Word Documents') }}</li>
                    <li>{{ __('Text Files') }}</li>
                </ul>
            </div>

            <div class="info-box">
                <h3>{{ __('How It Works') }}</h3>
                <ol class="steps-list">
                    <li>{{ __('Drag or select your files') }}</li>
                    <li>{{ __('Wait for upload to complete') }}</li>
                    <li>{{ __('Copy the shareable link') }}</li>
                </ol>
            </div>

            <div class="contact-box">
                <p>{{ __('Need help?') }}</p>
                <a href="mailto:support@genznewz.com">support@genznewz.com</a>
            </div>
        </aside>
    </div>
</section>

<style>
/* Upload Page - Simple Layout */
.upload-page {
    min-height: calc(100vh - 300px);
    padding: 40px 20px 60px;
    background: #f9f9f9;
}

.upload-container {
    max-width: 900px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 40px;
}

@media (max-width: 768px) {
    .upload-container {
        grid-template-columns: 1fr;
        max-width: 600px;
    }
    
    .upload-sidebar {
        display: none;
    }
}

/* Header */
.upload-header {
    grid-column: 1 / -1;
    text-align: center;
    margin-bottom: 10px;
}

.upload-title {
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 2.2rem;
    font-weight: 500;
    color: #121212;
    margin-bottom: 10px;
}

.upload-subtitle {
    color: #555;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
    margin-bottom: 15px;
}

.header-divider {
    height: 3px;
    background: #121212;
    max-width: 100px;
    margin: 0 auto;
}

/* Upload Box */
.upload-box {
    background: #fff;
    border: 1px solid #e2e2e2;
    padding: 35px;
}

/* Drop Zone */
.drop-zone {
    border: 2px dashed #ccc;
    background: #fafafa;
    padding: 60px 30px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.drop-zone:hover,
.drop-zone.dragover {
    border-color: #326891;
    background: #f0f7fb;
}

.drop-zone-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
}

.drop-icon {
    font-size: 3rem;
}

.drop-text {
    font-family: 'Inter', sans-serif;
    font-size: 1.1rem;
    font-weight: 500;
    color: #333;
    margin: 0;
}

.drop-formats {
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    color: #666;
    margin: 0;
}

/* Upload Progress */
.upload-progress {
    margin-top: 25px;
    padding: 20px;
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
}

.progress-file {
    color: #333;
    font-weight: 500;
}

.progress-percent {
    color: #326891;
    font-weight: 700;
}

.progress-bar-container {
    height: 10px;
    background: #ddd;
    border-radius: 5px;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #326891 0%, #4a7ca8 100%);
    width: 0%;
    transition: width 0.3s ease;
}

.progress-status {
    margin-top: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 0.85rem;
    color: #555;
}

/* Uploaded Files */
.uploaded-files {
    margin-top: 25px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.uploaded-file {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 18px;
    background: #f8f8f8;
    border: 1px solid #e2e2e2;
    font-family: 'Inter', sans-serif;
}

.file-icon {
    font-size: 1.8rem;
}

.file-info {
    flex: 1;
    min-width: 0;
}

.file-name {
    font-weight: 600;
    color: #222;
    font-size: 0.95rem;
    margin-bottom: 4px;
    word-break: break-all;
}

.file-link {
    font-size: 0.8rem;
    color: #326891;
    text-decoration: none;
    word-break: break-all;
}

.file-link:hover {
    text-decoration: underline;
}

.file-status {
    font-size: 0.8rem;
    padding: 5px 12px;
    background: #d4edda;
    color: #155724;
    font-weight: 500;
    white-space: nowrap;
}

.file-status.uploading {
    background: #fff3cd;
    color: #856404;
}

.file-status.error {
    background: #f8d7da;
    color: #721c24;
}

/* Sidebar */
.upload-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.info-box {
    background: #fff;
    border: 1px solid #e2e2e2;
    padding: 25px;
}

.info-box h3 {
    font-family: 'Space Grotesk', sans-serif;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #121212;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #121212;
}

.info-list,
.steps-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.info-list li,
.steps-list li {
    padding: 8px 0;
    padding-left: 20px;
    position: relative;
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    color: #444;
    border-bottom: 1px dotted #ddd;
}

.info-list li:last-child,
.steps-list li:last-child {
    border-bottom: none;
}

.info-list li::before {
    content: "•";
    position: absolute;
    left: 0;
    color: #326891;
    font-weight: bold;
}

.steps-list {
    counter-reset: step;
}

.steps-list li {
    counter-increment: step;
}

.steps-list li::before {
    content: counter(step);
    position: absolute;
    left: 0;
    color: #326891;
    font-weight: 700;
}

.contact-box {
    background: #121212;
    color: #fff;
    padding: 20px;
    text-align: center;
}

.contact-box p {
    margin: 0 0 8px;
    font-family: 'Inter', sans-serif;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.9;
}

.contact-box a {
    color: #fff;
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    text-decoration: none;
}

.contact-box a:hover {
    text-decoration: underline;
}

/* ==================== */
/* DARK MODE FIXES      */
/* ==================== */

body.dark-mode .upload-page {
    background: #0a0a0a;
}

body.dark-mode .upload-title {
    color: #f0f0f0;
}

body.dark-mode .upload-subtitle {
    color: #aaa;
}

body.dark-mode .header-divider {
    background: #555;
}

body.dark-mode .upload-box {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .drop-zone {
    background: #222;
    border-color: #444;
}

body.dark-mode .drop-zone:hover,
body.dark-mode .drop-zone.dragover {
    background: #1a2a3a;
    border-color: #4a7ca8;
}

body.dark-mode .drop-text {
    color: #e0e0e0;
}

body.dark-mode .drop-formats {
    color: #888;
}

body.dark-mode .upload-progress {
    background: #222;
    border-color: #333;
}

body.dark-mode .progress-file {
    color: #e0e0e0;
}

body.dark-mode .progress-bar-container {
    background: #444;
}

body.dark-mode .progress-status {
    color: #aaa;
}

body.dark-mode .uploaded-file {
    background: #222;
    border-color: #333;
}

body.dark-mode .file-name {
    color: #e0e0e0;
}

body.dark-mode .file-link {
    color: #5a9fd4;
}

body.dark-mode .info-box {
    background: #1a1a1a;
    border-color: #333;
}

body.dark-mode .info-box h3 {
    color: #f0f0f0;
    border-color: #555;
}

body.dark-mode .info-list li,
body.dark-mode .steps-list li {
    color: #bbb;
    border-color: #333;
}

body.dark-mode .contact-box {
    background: #222;
}

body.dark-mode .contact-box p {
    color: #888;
}
</style>

<script>
// Upload Configuration - Uses server-side proxy to avoid CORS issues
// API routes don't have language prefix middleware
const UPLOAD_URL = '/api/upload';
const MAX_FILE_BYTES = 100 * 1024 * 1024;

// DOM Elements
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadProgress = document.getElementById('uploadProgress');
const progressBar = uploadProgress.querySelector('.progress-bar');
const progressPercent = uploadProgress.querySelector('.progress-percent');
const progressFile = uploadProgress.querySelector('.progress-file');
const progressStatus = uploadProgress.querySelector('.progress-status');
const uploadedFiles = document.getElementById('uploadedFiles');

// Get CSRF token from meta tag or input
function getCsrfToken() {
    const tokenInput = document.querySelector('input[name="_token"]');
    if (tokenInput) return tokenInput.value;
    
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    if (metaToken) return metaToken.content;
    
    return '';
}

// Drag and Drop Events
dropZone.addEventListener('click', () => fileInput.click());

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');
    const files = Array.from(e.dataTransfer.files);
    files.forEach(uploadFile);
});

fileInput.addEventListener('change', (e) => {
    const files = Array.from(e.target.files);
    files.forEach(uploadFile);
    fileInput.value = '';
});

// Upload File via Server Proxy
async function uploadFile(file) {
    const fileId = 'file-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

    if (file.size > MAX_FILE_BYTES) {
        const fileElement = createFileElement(file, fileId);
        uploadedFiles.appendChild(fileElement);
        updateFileElement(fileElement, file, null, false);
        uploadProgress.style.display = 'block';
        progressFile.textContent = file.name;
        progressPercent.textContent = '0%';
        progressBar.style.width = '0%';
        progressBar.style.background = '#dc3545';
        progressStatus.textContent = 'Upload failed: files must be 100MB or smaller.';
        return;
    }
    
    // Create file element
    const fileElement = createFileElement(file, fileId);
    uploadedFiles.appendChild(fileElement);
    
    // Show progress
    uploadProgress.style.display = 'block';
    progressFile.textContent = file.name;
    progressPercent.textContent = '0%';
    progressBar.style.width = '0%';
    progressBar.style.background = 'linear-gradient(90deg, #326891 0%, #4a7ca8 100%)';
    progressStatus.textContent = 'Uploading...';
    
    try {
        // Create FormData
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', getCsrfToken());
        
        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        // Track upload progress
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressPercent.textContent = percent + '%';
            }
        });
        
        // Setup promise to handle response
        const uploadPromise = new Promise((resolve, reject) => {
            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        resolve(JSON.parse(xhr.responseText));
                    } catch (e) {
                        reject(new Error('Invalid response'));
                    }
                } else {
                    let errorMsg = 'Upload failed';
                    try {
                        const error = JSON.parse(xhr.responseText);
                        errorMsg = error.message || errorMsg;
                    } catch (e) {}
                    reject(new Error(errorMsg));
                }
            });
            
            xhr.addEventListener('error', () => reject(new Error('Network error - please try again')));
            xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));
        });
        
        // Open and send request to our server
        xhr.open('POST', UPLOAD_URL);
        xhr.send(formData);
        
        // Wait for upload
        const response = await uploadPromise;
        
        if (response.success) {
            // Update file element with success
            updateFileElement(fileElement, file, response.url, true);
            
            // Update progress
            progressStatus.textContent = 'Upload complete! Link generated below.';
            setTimeout(() => {
                uploadProgress.style.display = 'none';
            }, 2000);
        } else {
            throw new Error(response.message || 'Upload failed');
        }
        
    } catch (error) {
        console.error('Upload error:', error);
        updateFileElement(fileElement, file, null, false);
        progressStatus.textContent = 'Upload failed: ' + error.message;
        progressBar.style.background = '#dc3545';
    }
}

// Create file element
function createFileElement(file, fileId) {
    const div = document.createElement('div');
    div.className = 'uploaded-file';
    div.id = fileId;
    
    const icon = getFileIcon(file.type);
    
    div.innerHTML = `
        <span class="file-icon">${icon}</span>
        <div class="file-info">
            <div class="file-name">${file.name}</div>
            <div style="color: #888; font-size: 0.75rem;">${formatFileSize(file.size)}</div>
        </div>
        <span class="file-status uploading">Uploading...</span>
    `;
    
    return div;
}

// Update file element
function updateFileElement(element, file, url, success) {
    const statusEl = element.querySelector('.file-status');
    const infoEl = element.querySelector('.file-info');
    
    if (success && url) {
        infoEl.innerHTML += `
            <div><a href="${url}" target="_blank" class="file-link">${url}</a></div>
        `;
        statusEl.textContent = '✓ Done';
        statusEl.className = 'file-status';
    } else {
        statusEl.textContent = '✗ Failed';
        statusEl.className = 'file-status error';
    }
}

// Get file icon based on type
function getFileIcon(mimeType) {
    if (mimeType.startsWith('image/')) return '🖼️';
    if (mimeType.startsWith('video/')) return '🎬';
    if (mimeType === 'application/pdf') return '📄';
    if (mimeType.includes('word')) return '📝';
    return '📎';
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>
