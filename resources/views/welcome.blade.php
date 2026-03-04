@extends('layouts.app')

@section('title', 'CV ATS Analyst - MaxyCareer')

@section('header_title', 'CV ATS Analyst')
@section('header_subtitle', 'Optimize your resume for applicant tracking systems with AI.')

@section('content')
<div class="space-y-6 max-w-6xl">
    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="card p-5">
            <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-1">Total Analyzed</p>
            <h3 class="text-3xl font-bold text-gray-900">{{ number_format($total_analyzed) }}</h3>
            <p class="text-sm mt-2 flex items-center gap-1">
                <span class="badge-pill bg-success-50 text-success-500 text-[11px]">↑ 12%</span>
                <span class="text-gray-400 text-xs">vs last week</span>
            </p>
        </div>
        <div class="card p-5">
            <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-1">Avg. ATS Score</p>
            <h3 class="text-3xl font-bold text-gray-900">{{ number_format($avg_score, 1) }}%</h3>
            <p class="text-sm mt-2">
                <span class="badge-pill bg-primary-50 text-primary-500 text-[11px]">High Quality</span>
            </p>
        </div>
        <div class="card p-5">
            <p class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-1">Top Industry</p>
            <h3 class="text-3xl font-bold text-gray-900">{{ $top_industry }}</h3>
            <p class="text-gray-400 text-xs mt-2">Most active sector</p>
        </div>
    </div>

    <!-- Upload Zone -->
    <div id="upload-container" class="animate-fade-in" style="animation-delay: 150ms">
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Target Job Description <span class="text-gray-400 font-normal">(Optional)</span></label>
            <textarea id="job-description" class="w-full h-28 input-field rounded-lg p-3 text-sm resize-none" placeholder="Paste the job description here for a tailored ATS analysis..."></textarea>
        </div>

        <div id="drop-zone" class="card p-10 flex flex-col items-center justify-center text-center cursor-pointer border-2 border-dashed border-gray-300 hover:border-primary-500 hover:bg-primary-50/30 transition-all group relative">
            <div class="w-14 h-14 rounded-xl bg-primary-50 flex items-center justify-center mb-4 group-hover:bg-primary-100 transition-colors">
                <svg class="w-7 h-7 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-1 group-hover:text-primary-500 transition-colors">Drop your resume here</h3>
            <p class="text-gray-400 text-sm max-w-sm">Support PDF, DOCX (Max 10MB). Our AI will analyze your resume against standard ATS metrics.</p>
            <input type="file" id="file-input" class="hidden" accept=".pdf,.docx">
        </div>
    </div>

    <!-- Analysis Loading State -->
    <div id="loading-state" class="hidden py-16 text-center animate-fade-in">
        <div class="relative w-20 h-20 mx-auto mb-8">
            <div class="absolute inset-0 border-4 border-gray-200 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-primary-500 rounded-full border-t-transparent animate-spin"></div>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Analyzing Resume...</h3>
        <p class="text-gray-500 text-sm" id="loading-text">Extracting keywords and formatting data</p>
    </div>

    <!-- Analysis Results -->
    <div id="results-container" class="hidden space-y-6 animate-fade-in">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Overall Score -->
            <div class="lg:col-span-1 card p-6 flex flex-col items-center justify-center text-center">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-6 w-full text-left">Overall Match</h4>
                <div class="relative w-40 h-40 mb-4">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="80" cy="80" r="72" stroke="currentColor" stroke-width="8" fill="transparent" class="text-gray-100" />
                        <circle cx="80" cy="80" r="72" stroke="currentColor" stroke-width="10" fill="transparent" stroke-dasharray="452.39" stroke-dashoffset="452.39" id="score-circle" class="text-primary-500 transition-all duration-1000" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-4xl font-black text-gray-900" id="display-score">0%</span>
                        <span class="text-xs uppercase tracking-widest font-bold text-success-500 mt-1" id="display-label">Analyzing</span>
                    </div>
                </div>
                <p class="text-gray-500 text-sm leading-relaxed" id="display-summary"></p>
                <div class="mt-6 w-full flex flex-col gap-2">
                    <button id="generate-cv-btn" class="w-full py-2.5 px-4 btn-primary text-sm flex items-center justify-center gap-2">
                        <span id="gen-btn-text">Generate ATS Optimized CV</span>
                        <svg id="gen-spinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                    <button id="re-upload" class="w-full py-2.5 px-4 btn-outline text-sm">Upload Another</button>
                </div>
            </div>

            <!-- Detailed Metrics -->
            <div class="lg:col-span-2 card p-6">
                <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-8">Analysis Breakdown</h4>
                <div class="space-y-6" id="metrics-breakdown">
                    <!-- Dynamic metrics here -->
                </div>

                <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-lg bg-gray-50 border border-gray-100">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-3">Matching Keywords</p>
                        <div class="flex flex-wrap gap-1.5" id="matching-skills"></div>
                    </div>
                    <div class="p-4 rounded-lg bg-gray-50 border border-gray-100">
                        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-3">Suggested Keywords</p>
                        <div class="flex flex-wrap gap-1.5" id="missing-skills"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Insights -->
        <div class="card p-6">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-4">AI Actionable Insights</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3" id="insights-container">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    const uploadContainer = document.getElementById('upload-container');
    const loadingState = document.getElementById('loading-state');
    const resultsContainer = document.getElementById('results-container');
    const loadingText = document.getElementById('loading-text');
    const reUpload = document.getElementById('re-upload');
    const jobDesc = document.getElementById('job-description');
    const generateCvBtn = document.getElementById('generate-cv-btn');

    let currentAnalysis = null;

    const loadingSteps = [
        "Scanning document structure...",
        "Identifying key skills and technologies...",
        "Checking format compatibility with ATS...",
        "Evaluating readability and layout...",
        "Finalizing AI score calculation..."
    ];

    async function handleFile(file) {
        if (!file) return;
        
        const formData = new FormData();
        formData.append('cv', file);
        formData.append('job_description', jobDesc.value);
        
        uploadContainer.classList.add('hidden');
        loadingState.classList.remove('hidden');
        
        let step = 0;
        const interval = setInterval(() => {
            if (step < loadingSteps.length) {
                loadingText.innerText = loadingSteps[step];
                step++;
            }
        }, 800);

        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 60000);

            const response = await fetch('/analyze', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData,
                signal: controller.signal
            });
            clearTimeout(timeoutId);

            const data = await response.json();
            clearInterval(interval);

            if (data.error) {
                alert(data.error);
                reUpload.click();
                return;
            }

            currentAnalysis = data;
            renderResults(data);
        } catch (error) {
            clearInterval(interval);
            console.error('Error:', error);
            alert('An error occurred during analysis.');
            reUpload.click();
        }
    }

    function renderResults(data) {
        loadingState.classList.add('hidden');
        resultsContainer.classList.remove('hidden');

        document.getElementById('display-score').innerText = `${data.overall_score}%`;
        document.getElementById('display-label').innerText = data.score_label;
        document.getElementById('display-summary').innerText = data.summary;
        
        const labelEl = document.getElementById('display-label');
        labelEl.className = 'text-xs uppercase tracking-widest font-bold mt-1 ' + 
            (data.overall_score > 80 ? 'text-success-500' : data.overall_score > 60 ? 'text-primary-500' : 'text-error-500');

        // Circle progress (452.39 = 2 * PI * 72)
        const offset = 452.39 - (452.39 * data.overall_score / 100);
        document.getElementById('score-circle').style.strokeDashoffset = offset;

        const metricsContainer = document.getElementById('metrics-breakdown');
        metricsContainer.innerHTML = '';
        Object.entries(data.metrics).forEach(([key, score]) => {
            const label = key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            const colorClass = score > 80 ? 'bg-success-500' : score > 60 ? 'bg-primary-500' : 'bg-error-500';
            metricsContainer.innerHTML += `
                <div class="space-y-2">
                    <div class="flex justify-between items-end">
                        <span class="font-semibold text-gray-900">${label}</span>
                        <span class="text-gray-500 font-medium text-sm">${score}%</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                        <div class="h-full ${colorClass} rounded-full transition-all" style="width: ${score}%"></div>
                    </div>
                </div>
            `;
        });

        document.getElementById('matching-skills').innerHTML = data.matching_keywords.map(s => 
            `<span class="badge-pill bg-primary-50 text-primary-500 border border-primary-100">${s}</span>`
        ).join('');
        
        document.getElementById('missing-skills').innerHTML = data.missing_keywords.map(s => 
            `<span class="badge-pill bg-accent-50 text-accent-500 border border-accent-100">${s}</span>`
        ).join('');

        const insightsContainer = document.getElementById('insights-container');
        insightsContainer.innerHTML = data.insights.map(i => {
            const colors = {
                success: 'bg-success-50 border-success-500/20 text-success-500',
                warning: 'bg-yellow-50 border-yellow-500/20 text-yellow-600',
                error: 'bg-error-50 border-error-500/20 text-error-500'
            };
            const icons = { success: '✓', warning: '!', error: '✕' };
            return `
                <div class="p-3 rounded-lg border flex gap-3 ${colors[i.type]}">
                    <div class="font-bold text-sm">${icons[i.type]}</div>
                    <p class="text-sm leading-relaxed">${i.text}</p>
                </div>
            `;
        }).join('');
    }

    generateCvBtn.addEventListener('click', async () => {
        if (!currentAnalysis) return;
        
        const btnText = document.getElementById('gen-btn-text');
        const spinner = document.getElementById('gen-spinner');
        
        btnText.innerText = 'Generating ATS CV...';
        spinner.classList.remove('hidden');
        generateCvBtn.disabled = true;

        try {
            const response = await fetch('/generate-cv', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    cv_text: currentAnalysis.raw_text,
                    analysis: currentAnalysis
                })
            });

            const freshCvData = await response.json();
            
            if (freshCvData.error) {
                alert(freshCvData.error);
            } else {
                await window.CvGenerator.download(freshCvData);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to generate CV.');
        } finally {
            btnText.innerText = 'Generate ATS Optimized CV';
            spinner.classList.add('hidden');
            generateCvBtn.disabled = false;
        }
    });

    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-primary-500', 'bg-primary-50/30');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-primary-500', 'bg-primary-50/30');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-primary-500', 'bg-primary-50/30');
        handleFile(e.dataTransfer.files[0]);
    });

    reUpload.addEventListener('click', () => {
        resultsContainer.classList.add('hidden');
        uploadContainer.classList.remove('hidden');
        fileInput.value = '';
        currentAnalysis = null;
    });
});
</script>
@endsection
