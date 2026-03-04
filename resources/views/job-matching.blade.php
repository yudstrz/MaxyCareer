@extends('layouts.app')

@section('title', 'AI Job Matching - MaxyCareer')

@section('header_title', 'Job Matching Engine')
@section('header_subtitle', 'Find the top 5 roles perfectly matching your resume using AI.')

@section('content')
<div class="space-y-8">
    
    <!-- Job Database Info -->
    <div class="glass-card p-6 p-relative overflow-hidden group flex items-center justify-between">
        <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/10 rounded-full -mr-16 -mt-16 blur-3xl transition-all"></div>
        <div>
            <h3 class="text-xl font-bold mb-1">Local AI Engine</h3>
            <p class="text-white/40 text-sm">Matching against 100 vectorized career profiles locally in your browser.</p>
        </div>
        <div id="model-status" class="px-3 py-1.5 rounded-full bg-white/5 border border-white/10 text-xs font-bold text-white/40 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-warning-500 animate-pulse"></span> Initializing Pipeline...
        </div>
    </div>

    <!-- Upload Zone -->
    <div id="upload-container" class="animate-fade-in" style="animation-delay: 200ms">
        <h3 class="text-2xl font-bold mb-2 tracking-tight group-hover:text-primary-400 transition-colors">Select your CV or Paste Text</h3>
        <p class="text-white/40 max-w-xl mb-4">Drop a PDF/DOCX below, or paste the text content directly.</p>
        
        <div id="drop-zone" class="glass p-8 rounded-3xl border-2 border-dashed border-white/10 flex flex-col items-center justify-center text-center cursor-pointer hover:border-primary-500/40 hover:bg-white/[0.02] transition-all group relative overflow-hidden disabled-area mb-6">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div class="w-16 h-16 rounded-2xl bg-white/5 flex items-center justify-center mb-4 border border-white/5 group-hover:bg-primary-500/10 group-hover:border-primary-500/20 transition-all">
                <svg class="w-8 h-8 text-white/40 group-hover:text-primary-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            
            <p class="text-white/80 font-bold">📄 Click to Upload or Drag & Drop CV</p>
            <p class="text-white/40 text-sm mt-1">Supports PDF & DOCX formats (Max 10MB)</p>
            <input type="file" id="cv-file-input" class="hidden" accept=".pdf,.docx">
        </div>

        <div class="flex flex-col items-center">
            <textarea id="cv-text-input" class="w-full max-w-2xl h-48 glass rounded-2xl p-4 text-sm text-white border border-white/5 focus:border-primary-500/40 outline-none transition-all resize-none shadow-inner" placeholder="Paste your comprehensive CV text here..." disabled></textarea>

            <button id="start-match-btn" class="mt-6 py-3 px-8 rounded-xl bg-gradient-to-r from-primary-500 to-accent-500 text-sm font-bold shadow-lg shadow-primary-500/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Find Matches
            </button>
        </div>
    </div>

    <!-- Analyzing State -->
    <div id="loading-state" class="hidden py-20 text-center animate-fade-in">
        <div class="relative w-32 h-32 mx-auto mb-10">
            <div class="absolute inset-0 border-4 border-primary-500/20 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-primary-500 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <h3 class="text-2xl font-bold mb-2">Generating Embeddings...</h3>
        <p class="text-white/40" id="loading-text">Vectorizing CV content through Xenova/all-MiniLM-L6-v2</p>
    </div>

    <!-- Match Results -->
    <div id="results-container" class="hidden animate-fade-in space-y-6">
        <div class="flex justify-between items-end mb-6">
            <div>
                <h3 class="text-2xl font-bold">Top 5 Job Matches</h3>
                <p class="text-white/40 text-sm">Roles identified as highest cosine similarity to your professional vectors.</p>
            </div>
            <button id="re-upload-btn" class="py-2 px-4 rounded-lg glass border border-white/10 text-sm font-bold hover:bg-white/5 transition-all">Scan New CV</button>
        </div>

        <div id="matches-list" class="grid grid-cols-1 gap-4">
            <!-- Dynamic Matches Here -->
        </div>
    </div>

</div>

<style>
.disabled-area { opacity: 0.6; pointer-events: none; }
#drop-zone.drag-active { border-color: rgb(59 130 246 / 0.4); background-color: rgba(255,255,255,0.04); }
</style>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const statusEl = document.getElementById('model-status');
    const inputArea = document.getElementById('cv-text-input');
    const matchBtn = document.getElementById('start-match-btn');
    const dropZone = document.getElementById('drop-zone');
    const uploadContainer = document.getElementById('upload-container');
    const loadingState = document.getElementById('loading-state');
    const resultsContainer = document.getElementById('results-container');
    const matchesList = document.getElementById('matches-list');
    const loadingText = document.getElementById('loading-text');

    // 1. Initialize Transformers.js
    try {
        await window.JobMatcherEngine.init((msg) => {
            statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-warning-500 animate-pulse"></span> <span class="text-white/80">${msg}</span>`;
        });
        
        // Ready state
        statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-success-500 shadow-[0_0_8px_rgba(34,197,94,0.6)]"></span> <span class="text-success-400">Pipeline Ready</span>`;
        statusEl.classList.remove('bg-white/5');
        statusEl.classList.add('bg-success-500/10', 'border-success-500/20');
        
        dropZone.classList.remove('disabled-area');
        inputArea.disabled = false;
        
    } catch (e) {
        statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-error-500"></span> <span class="text-error-400">Initialization Failed</span>`;
        alert("Model failed to load. Check console for details.");
        console.error(e);
    }

    // Handlers
    inputArea.addEventListener('input', () => {
        if(inputArea.value.trim().length > 20) {
            matchBtn.disabled = false;
        } else {
            matchBtn.disabled = true;
        }
    });

    matchBtn.addEventListener('click', async () => {
        const text = inputArea.value.trim();
        if(!text) return;

        uploadContainer.classList.add('hidden');
        loadingState.classList.remove('hidden');

        try {
            loadingText.innerText = "Vectorizing Document (Local CPU)...";
            const topMatches = await window.JobMatcherEngine.findMatches(text, 5);
            
            loadingState.classList.add('hidden');
            renderMatches(topMatches);
        } catch (e) {
            loadingState.classList.add('hidden');
            uploadContainer.classList.remove('hidden');
            alert("Error matching jobs.");
            console.error(e);
        }
    });

    document.getElementById('re-upload-btn').addEventListener('click', () => {
        resultsContainer.classList.add('hidden');
        uploadContainer.classList.remove('hidden');
        inputArea.value = '';
        document.getElementById('cv-file-input').value = '';
        matchBtn.disabled = true;
    });

    // File Upload Handlers
    const fileInput = document.getElementById('cv-file-input');
    
    dropZone.addEventListener('click', (e) => {
        if(e.target !== fileInput) {
            fileInput.click();
        }
    });
    
    fileInput.addEventListener('change', (e) => handleFileUpload(e.target.files[0]));
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-active');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-active');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-active');
        handleFileUpload(e.dataTransfer.files[0]);
    });

    async function handleFileUpload(file) {
        if (!file) return;
        
        const formData = new FormData();
        formData.append('cv', file);
        
        // Show local loading just for extraction
        inputArea.value = "Extracting text from document...";
        matchBtn.disabled = true;

        try {
            const response = await fetch('/job-matching/extract', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });

            if(!response.ok) {
               const textErr = await response.text();
               console.error("Server Error:", textErr);
               alert("Server Error " + response.status + ". Please check F12 console.");
               inputArea.value = '';
               return;
            }

            const data = await response.json();

            if (data.error) {
                alert(data.error);
                inputArea.value = '';
                return;
            }

            inputArea.value = data.text;
            matchBtn.disabled = false;
        } catch (error) {
            console.error('Extraction Error:', error);
            alert('Failed to extract text from the document.');
            inputArea.value = '';
        }
    }

    function renderMatches(matches) {
        matchesList.innerHTML = '';
        matches.forEach((job, index) => {
            
            // Clean up the job fields since the generic parser might output strings or undefined
            const title = job.Job_Title || job.title || job.Position || 'Unknown Role';
            const company = job.Company || job.company || 'Unknown Company';
            const req = job.Requirements || job.Description || job.Qualifications || 'N/A';
            const score = parseFloat(job.matchScore);
            
            // Generate visual color maps based on score
            let colorCls = 'text-error-400';
            let bgCls = 'bg-error-500/10 border-error-500/20';
            if (score > 80) { colorCls = 'text-success-400'; bgCls = 'bg-success-500/10 border-success-500/20'; }
            else if (score > 60) { colorCls = 'text-primary-400'; bgCls = 'bg-primary-500/10 border-primary-500/20'; }
            else if (score > 40) { colorCls = 'text-warning-400'; bgCls = 'bg-warning-500/10 border-warning-500/20'; }


            matchesList.innerHTML += `
                <div class="glass-card p-6 border-l-4 ${bgCls.split(' ')[1].replace('.20', '')} border-l-[color:var(--tw-border-opacity)] relative overflow-hidden group">
                    <div class="absolute inset-0 bg-white/[0.02] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-1">Rank #${index + 1}</div>
                            <h4 class="text-xl font-bold text-white group-hover:text-primary-400 transition-colors">${typeof title === 'string' ? title : Object.values(job)[1] || 'Unknown'}</h4>
                            <p class="text-white/60 text-sm">${typeof company === 'string' ? company : Object.values(job)[0] || 'Unknown'}</p>
                        </div>
                        <div class="text-right">
                            <div class="px-3 py-1 rounded-lg ${bgCls} ${colorCls} font-bold text-lg inline-block">
                                ${score}%
                            </div>
                            <div class="text-[10px] uppercase text-white/40 mt-1">Cosine Sim</div>
                        </div>
                    </div>
                    <div class="p-4 rounded-xl glass border border-white/5 text-xs text-white/60 leading-relaxed max-h-32 overflow-y-auto">
                        ${typeof req === 'string' ? req.substring(0, 300) : JSON.stringify(job).substring(0, 300)}...
                    </div>
                </div>
            `;
        });

        resultsContainer.classList.remove('hidden');
    }
});
</script>
@endsection
