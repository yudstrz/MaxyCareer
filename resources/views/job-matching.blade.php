@extends('layouts.app')

@section('title', 'AI Job Matching - MaxyCareer')

@section('header_title', 'Job Matching Engine')
@section('header_subtitle', 'Temukan 10 pekerjaan paling cocok dengan CV Anda menggunakan AI.')

@section('content')
<div class="space-y-6 max-w-5xl">
    
    <!-- Job Database Info -->
    <div class="card p-5 flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-gray-900 mb-0.5">Local AI Engine</h3>
            <p class="text-gray-400 text-sm">Mencocokkan CV Anda dengan 200+ profil karier menggunakan AI.</p>
        </div>
        <div id="model-status" class="badge-pill bg-yellow-50 border border-yellow-200 text-yellow-600 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span> Initializing...
        </div>
    </div>

    <!-- Upload Zone -->
    <div id="upload-container" class="animate-fade-in" style="animation-delay: 150ms">
        <h3 class="text-lg font-bold text-gray-900 mb-1">Select your CV or Paste Text</h3>
        <p class="text-gray-400 text-sm max-w-xl mb-4">Drop a PDF/DOCX below, or paste the text content directly.</p>
        
        <div id="drop-zone" class="card p-8 flex flex-col items-center justify-center text-center cursor-pointer border-2 border-dashed border-gray-300 hover:border-primary-500 hover:bg-primary-50/30 transition-all group disabled-area mb-4">
            <div class="w-12 h-12 rounded-xl bg-primary-50 flex items-center justify-center mb-3 group-hover:bg-primary-100 transition-colors">
                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold text-sm">📄 Click to Upload or Drag & Drop CV</p>
            <p class="text-gray-400 text-xs mt-1">Supports PDF & DOCX formats (Max 10MB)</p>
            <input type="file" id="cv-file-input" class="hidden" accept=".pdf,.docx">
        </div>

        <div class="flex flex-col items-center">
            <textarea id="cv-text-input" class="w-full max-w-2xl h-40 input-field rounded-lg p-3 text-sm resize-none" placeholder="Paste your comprehensive CV text here..." disabled></textarea>

            <button id="start-match-btn" class="mt-4 py-2.5 px-6 btn-primary text-sm flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                Find Matches
            </button>
        </div>
    </div>

    <!-- Analyzing State -->
    <div id="loading-state" class="hidden py-16 text-center animate-fade-in">
        <div class="relative w-16 h-16 mx-auto mb-6">
            <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-primary-500 rounded-full animate-spin" style="border-right-color: transparent; border-bottom-color: transparent;"></div>
        </div>
        <p id="loading-text" class="text-gray-500 text-sm font-medium">Analyzing...</p>
    </div>

    <!-- Match Results -->
    <div id="results-container" class="hidden animate-fade-in space-y-4">
        <div class="flex justify-between items-end mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Top 10 Job Matches</h3>
                <p class="text-gray-400 text-sm">Rekomendasi pekerjaan paling sesuai berdasarkan analisis AI.</p>
            </div>
            <button id="re-upload-btn" class="py-2 px-4 btn-outline text-sm">Scan New CV</button>
        </div>

        <!-- Filter Pills -->
        <div id="filter-bar" class="flex flex-wrap gap-2 mb-2">
            <button class="filter-pill active" data-filter="all">Semua</button>
        </div>

        <div id="matches-list" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Dynamic Matches Here -->
        </div>

        <div id="job-listings-section" class="mt-8 hidden">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-lg font-bold text-gray-900">🔍 Lowongan Aktif</h3>
                    <p id="listings-subtitle" class="text-gray-400 text-sm">Lowongan terkait dari berbagai platform.</p>
                </div>
            </div>

            <div id="listings-loading" class="hidden w-full mb-4">
                <div class="h-1.5 w-full bg-gray-100 rounded-full overflow-hidden relative">
                    <div class="absolute top-0 left-0 h-full bg-primary-500 rounded-full w-1/3 animate-[slide_1.5s_ease-in-out_infinite_alternate]"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1.5 animate-pulse">Menghubungkan ke server RSS untuk mencari lowongan live...</p>
            </div>

            <!-- Search Links -->
            <div id="search-links" class="flex flex-wrap gap-2 mb-4"></div>

            <!-- RSS Results -->
            <div id="rss-results" class="grid grid-cols-1 md:grid-cols-2 gap-3"></div>
            <p id="no-rss-results" class="hidden text-gray-400 text-sm text-center py-6">Tidak ada lowongan ditemukan untuk filter ini.</p>
        </div>
    </div>

</div>

<style>
.disabled-area { opacity: 0.6; pointer-events: none; }
#drop-zone.drag-active { border-color: #2563EB; background-color: rgba(37, 99, 235, 0.04); }
.filter-pill {
    padding: 6px 14px;
    border-radius: 9999px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #E5E7EB;
    background: white;
    color: #6B7280;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.filter-pill:hover { border-color: #2563EB; color: #2563EB; }
.filter-pill.active { background: #2563EB; color: white; border-color: #2563EB; }
.match-card { transition: all 0.2s; }
.match-card.dimmed { opacity: 0.35; transform: scale(0.98); }
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

    let allMatches = []; // Store all 10 matches

    // 1. Initialize Transformers.js
    try {
        await window.JobMatcherEngine.init((msg) => {
            statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-yellow-500 animate-pulse"></span> <span class="text-yellow-700">${msg}</span>`;
        });
        
        statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-success-500"></span> <span class="text-success-500 font-semibold">Pipeline Ready</span>`;
        statusEl.className = 'badge-pill bg-success-50 border border-green-200 text-success-500 flex items-center gap-2';
        
        dropZone.classList.remove('disabled-area');
        inputArea.disabled = false;
        
    } catch (e) {
        statusEl.innerHTML = `<span class="w-2 h-2 rounded-full bg-error-500"></span> <span class="text-error-500">Initialization Failed</span>`;
        statusEl.className = 'badge-pill bg-error-50 border border-red-200 text-error-500 flex items-center gap-2';
        alert("Model failed to load. Check console for details.");
        console.error(e);
    }

    // Handlers
    inputArea.addEventListener('input', () => {
        matchBtn.disabled = inputArea.value.trim().length <= 20;
    });

    matchBtn.addEventListener('click', async () => {
        const text = inputArea.value.trim();
        if(!text) return;

        uploadContainer.classList.add('hidden');
        loadingState.classList.remove('hidden');

        try {
            loadingText.innerText = "Vectorizing Document (Local CPU)...";
            const topMatches = await window.JobMatcherEngine.findMatches(text, 10);
            
            loadingState.classList.add('hidden');
            allMatches = topMatches;
            renderMatches(topMatches);
            buildFilterPills(topMatches);
            fetchJobListings(topMatches);
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
        allMatches = [];
    });

    // File Upload Handlers
    const fileInput = document.getElementById('cv-file-input');
    
    dropZone.addEventListener('click', (e) => {
        if(e.target !== fileInput) fileInput.click();
    });
    
    fileInput.addEventListener('change', (e) => handleFileUpload(e.target.files[0]));
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-active');
    });
    
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-active'));
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-active');
        handleFileUpload(e.dataTransfer.files[0]);
    });

    async function handleFileUpload(file) {
        if (!file) return;
        
        const formData = new FormData();
        formData.append('cv', file);
        
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
               alert("Server Error " + response.status);
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
            const title = job.Okupasi || job.Job_Title || 'Unknown Role';
            const area = job.Area_Fungsi || 'General';
            const score = parseFloat(job.matchScore);
            
            let scoreBg, scoreText, borderColor;
            if (score > 80) {
                scoreBg = 'bg-success-50'; scoreText = 'text-success-500'; borderColor = 'border-l-green-500';
            } else if (score > 60) {
                scoreBg = 'bg-primary-50'; scoreText = 'text-primary-500'; borderColor = 'border-l-primary-500';
            } else if (score > 40) {
                scoreBg = 'bg-yellow-50'; scoreText = 'text-yellow-600'; borderColor = 'border-l-yellow-500';
            } else {
                scoreBg = 'bg-error-50'; scoreText = 'text-error-500'; borderColor = 'border-l-red-500';
            }

            matchesList.innerHTML += `
                <div class="match-card card p-4 border-l-4 ${borderColor} flex flex-col hover:shadow-md transition-shadow" data-role="${title}">
                    <div class="flex justify-between items-start mb-2 gap-2">
                        <div class="min-w-0">
                            <div class="text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Rank #${index + 1}</div>
                            <h4 class="text-sm font-bold text-gray-900 truncate" title="${title}">${title}</h4>
                            <p class="text-gray-500 text-xs truncate">${area}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="px-2 py-1 rounded-md ${scoreBg} ${scoreText} font-bold text-xs inline-block">
                                ${score}%
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto p-2 rounded-md bg-gray-50 border border-gray-100 text-[10px] text-gray-600 leading-snug line-clamp-2" title="${job.Unit_Kompetensi || 'N/A'}">
                        <span class="font-semibold text-gray-700">Skills:</span> ${job.Unit_Kompetensi || 'N/A'}
                    </div>
                </div>
            `;
        });

        resultsContainer.classList.remove('hidden');
    }

    function buildFilterPills(matches) {
        const filterBar = document.getElementById('filter-bar');
        filterBar.innerHTML = '<button class="filter-pill active" data-filter="all">Semua (${matches.length})</button>'.replace('${matches.length}', matches.length);

        matches.forEach((job, index) => {
            const title = job.Okupasi || 'Unknown';
            filterBar.innerHTML += `<button class="filter-pill" data-filter="${title}">${title}</button>`;
        });

        // Filter click handlers
        filterBar.querySelectorAll('.filter-pill').forEach(pill => {
            pill.addEventListener('click', () => {
                filterBar.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
                pill.classList.add('active');

                const filter = pill.dataset.filter;
                
                // Filter match cards
                document.querySelectorAll('.match-card').forEach(card => {
                    if (filter === 'all') {
                        card.classList.remove('dimmed', 'hidden');
                    } else {
                        if (card.dataset.role === filter) {
                            card.classList.remove('dimmed', 'hidden');
                        } else {
                            card.classList.add('dimmed');
                        }
                    }
                });

                // Fetch job listings for selected filter
                if (filter === 'all') {
                    fetchJobListings(allMatches);
                } else {
                    const selectedJob = allMatches.find(j => (j.Okupasi || '') === filter);
                    if (selectedJob) fetchJobListings([selectedJob]);
                }
            });
        });
    }

    async function fetchJobListings(matches) {
        const section = document.getElementById('job-listings-section');
        const rssResults = document.getElementById('rss-results');
        const searchLinks = document.getElementById('search-links');
        const loading = document.getElementById('listings-loading');
        const noResults = document.getElementById('no-rss-results');
        const subtitle = document.getElementById('listings-subtitle');

        section.classList.remove('hidden');
        loading.classList.remove('hidden');
        noResults.classList.add('hidden');
        rssResults.innerHTML = '';
        searchLinks.innerHTML = '';

        // Build query from matched role names
        const roleNames = matches.map(j => j.Okupasi || '').filter(r => r);
        const query = roleNames.slice(0, 3).join(' ').replace(/[()\/]/g, ' ').trim();
        subtitle.textContent = `Mencari lowongan untuk: ${roleNames.slice(0, 3).join(', ')}`;

        try {
            const response = await fetch(`/api/job-feeds?q=${encodeURIComponent(query)}`);
            const data = await response.json();
            loading.classList.add('hidden');

            // Render search links
            if (data.searchLinks) {
                data.searchLinks.forEach(link => {
                    searchLinks.innerHTML += `
                        <a href="${link.url}" target="_blank" rel="noopener" 
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 bg-white hover:border-primary-500 hover:bg-primary-50 transition-all text-sm font-medium text-gray-700 hover:text-primary-600">
                            ${link.icon} ${link.name}
                        </a>
                    `;
                });
            }

            // Render RSS results
            if (data.jobs && data.jobs.length > 0) {
                data.jobs.forEach(job => {
                    rssResults.innerHTML += `
                        <a href="${job.link}" target="_blank" rel="noopener" 
                           class="card p-4 hover:shadow-md transition-all hover:border-primary-200 block">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h4 class="text-sm font-bold text-gray-900 truncate">${job.title}</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">${job.company}</p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span class="badge-pill text-[10px] bg-gray-100 text-gray-500">${job.source}</span>
                                        <span class="text-[10px] text-gray-400">${job.location}</span>
                                        ${job.date ? `<span class="text-[10px] text-gray-400">· ${job.date}</span>` : ''}
                                    </div>
                                </div>
                                <svg class="w-4 h-4 text-gray-300 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                            </div>
                        </a>
                    `;
                });
            } else {
                noResults.classList.remove('hidden');
            }
        } catch (e) {
            loading.classList.add('hidden');
            console.error('RSS fetch error:', e);
            noResults.textContent = 'Gagal memuat lowongan. Coba gunakan link pencarian di atas.';
            noResults.classList.remove('hidden');
        }
    }
});
</script>
@endsection
