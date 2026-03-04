@extends('layouts.app')

@section('title', 'CV ATS Analyst - MaxyCareer')

@section('header_title', 'CV ATS Analyst Engine')
@section('header_subtitle', 'Optimize your resume for applicant tracking systems with AI.')

@section('content')
<div class="space-y-8">
    <!-- Top Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="glass-card p-6 p-relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/10 rounded-full -mr-16 -mt-16 blur-3xl group-hover:bg-primary-500/20 transition-all"></div>
            <p class="text-white/40 text-xs font-bold uppercase tracking-wider mb-2">Total Analyzed</p>
            <h3 class="text-4xl font-bold">{{ number_format($total_analyzed) }}</h3>
            <p class="text-success-400 text-xs mt-2 flex items-center gap-1">
                <span>↑ 12%</span>
                <span class="text-white/20">vs last week</span>
            </p>
        </div>
        <div class="glass-card p-6 p-relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-accent-500/10 rounded-full -mr-16 -mt-16 blur-3xl group-hover:bg-accent-500/20 transition-all"></div>
            <p class="text-white/40 text-xs font-bold uppercase tracking-wider mb-2">Avg. ATS Score</p>
            <h3 class="text-4xl font-bold">{{ number_format($avg_score, 1) }}%</h3>
            <p class="text-primary-400 text-xs mt-2 flex items-center gap-1">
                <span>High Quality</span>
            </p>
        </div>
        <div class="glass-card p-6 p-relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-success-500/10 rounded-full -mr-16 -mt-16 blur-3xl group-hover:bg-success-500/20 transition-all"></div>
            <p class="text-white/40 text-xs font-bold uppercase tracking-wider mb-2">Top Industry</p>
            <h3 class="text-4xl font-bold italic">{{ $top_industry }}</h3>
            <p class="text-white/20 text-xs mt-2 italic">Software Engineering</p>
        </div>
    </div>

    <!-- Upload Zone -->
    <div id="upload-container" class="animate-fade-in" style="animation-delay: 200ms">
        <div class="mb-6">
            <label class="block text-sm font-bold uppercase tracking-widest text-white/40 mb-3 ml-1">Target Job Description (Optional)</label>
            <textarea id="job-description" class="w-full h-32 glass rounded-2xl p-4 text-sm text-white/80 border border-white/5 focus:border-primary-500/40 outline-none transition-all resize-none" placeholder="Paste the job description here for a tailored ATS analysis..."></textarea>
        </div>

        <div id="drop-zone" class="glass p-12 rounded-3xl border-2 border-dashed border-white/10 flex flex-col items-center justify-center text-center cursor-pointer hover:border-primary-500/40 hover:bg-white/[0.02] transition-all group relative overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-primary-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
            
            <div class="w-20 h-20 rounded-2xl bg-white/5 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-500 group-hover:bg-primary-500/10 border border-white/5">
                <svg class="w-10 h-10 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
            </div>
            
            <h3 class="text-2xl font-bold mb-2 tracking-tight group-hover:text-primary-400 transition-colors">Drop your resume here</h3>
            <p class="text-white/40 max-w-sm">Support PDF, DOCX (Max 10MB). Our AI will analyze your resume against standard ATS metrics.</p>
            <input type="file" id="file-input" class="hidden" accept=".pdf,.docx">
        </div>
    </div>

    <!-- Analysis Loading State -->
    <div id="loading-state" class="hidden py-20 text-center animate-fade-in">
        <div class="relative w-32 h-32 mx-auto mb-10">
            <div class="absolute inset-0 border-4 border-primary-500/20 rounded-full"></div>
            <div class="absolute inset-0 border-4 border-primary-500 rounded-full border-t-transparent animate-spin"></div>
            <div class="absolute inset-4 border-2 border-accent-500/20 rounded-full"></div>
            <div class="absolute inset-4 border-2 border-accent-500 rounded-full border-b-transparent animate-spin-reverse"></div>
        </div>
        <h3 class="text-2xl font-bold mb-2">Analyzing Resume...</h3>
        <p class="text-white/40" id="loading-text">Extracting keywords and formatting data</p>
    </div>

    <!-- Analysis Results -->
    <div id="results-container" class="hidden space-y-8 animate-fade-in">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Overall Score -->
            <div class="lg:col-span-1 glass-card p-8 flex flex-col items-center justify-center text-center">
                <h4 class="text-sm font-bold uppercase tracking-widest text-white/40 mb-8 w-full text-left">Overall Match</h4>
                <div class="relative w-48 h-48 mb-6">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="8" fill="transparent" class="text-white/5" />
                        <circle cx="96" cy="96" r="88" stroke="currentColor" stroke-width="12" fill="transparent" stroke-dasharray="552.92" stroke-dashoffset="552.92" id="score-circle" class="text-primary-500 drop-shadow-[0_0_15px_rgba(59,130,246,0.5)] transition-all duration-1000" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-5xl font-black text-white" id="display-score">0%</span>
                        <span class="text-xs uppercase tracking-widest font-bold text-success-400 mt-1" id="display-label">Analyzing</span>
                    </div>
                </div>
                <p class="text-white/60 text-sm leading-relaxed" id="display-summary"></p>
                <div class="mt-8 w-full flex flex-col gap-3">
                    <button id="generate-cv-btn" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-primary-500 to-accent-500 text-sm font-bold shadow-lg shadow-primary-500/20 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                        <span id="gen-btn-text">Generate ATS Optimized CV</span>
                        <svg id="gen-spinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </button>
                    <button id="re-upload" class="w-full py-3 px-4 rounded-xl glass border border-white/10 text-sm font-bold hover:bg-white/5 transition-all">Upload Another</button>
                </div>
            </div>

            <!-- Detailed Metrics -->
            <div class="lg:col-span-2 glass-card p-8">
                <h4 class="text-sm font-bold uppercase tracking-widest text-white/40 mb-10">Analysis Breakdown</h4>
                <div class="space-y-8" id="metrics-breakdown">
                    <!-- Dynamic metrics here -->
                </div>

                <div class="mt-12 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Matching Keywords</p>
                        <div class="flex flex-wrap gap-2" id="matching-skills"></div>
                    </div>
                    <div class="p-4 rounded-2xl bg-white/[0.02] border border-white/5">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-white/40 mb-3">Suggested Keywords</p>
                        <div class="flex flex-wrap gap-2" id="missing-skills"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Insights -->
        <div class="glass-card p-8">
            <h4 class="text-sm font-bold uppercase tracking-widest text-white/40 mb-6">AI Actionable Insights</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="insights-container">
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
            const timeoutId = setTimeout(() => controller.abort(), 60000); // 60s timeout

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

        // Render Score
        document.getElementById('display-score').innerText = `${data.overall_score}%`;
        document.getElementById('display-label').innerText = data.score_label;
        document.getElementById('display-summary').innerText = data.summary;
        
        // Label color
        const labelEl = document.getElementById('display-label');
        labelEl.className = 'text-xs uppercase tracking-widest font-bold mt-1 ' + 
            (data.overall_score > 80 ? 'text-success-400' : data.overall_score > 60 ? 'text-primary-400' : 'text-error-400');

        // Circle progress (SVG dash offset logic: 552.92 is circumference)
        const offset = 552.92 - (552.92 * data.overall_score / 100);
        document.getElementById('score-circle').style.strokeDashoffset = offset;

        // Render Metrics
        const metricsContainer = document.getElementById('metrics-breakdown');
        metricsContainer.innerHTML = '';
        Object.entries(data.metrics).forEach(([key, score]) => {
            const label = key.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            const colorClass = score > 80 ? 'bg-success-500' : score > 60 ? 'bg-primary-500' : 'bg-error-500';
            metricsContainer.innerHTML += `
                <div class="space-y-3">
                    <div class="flex justify-between items-end">
                        <span class="font-bold tracking-tight text-lg">${label}</span>
                        <span class="text-white/60 font-medium">${score}%</span>
                    </div>
                    <div class="h-3 bg-white/5 rounded-full overflow-hidden border border-white/5">
                        <div class="h-full ${colorClass} rounded-full" style="width: ${score}%"></div>
                    </div>
                </div>
            `;
        });

        // Keywords
        document.getElementById('matching-skills').innerHTML = data.matching_keywords.map(s => 
            `<span class="px-2.5 py-1 rounded-lg bg-primary-500/10 border border-primary-500/20 text-primary-400 text-xs font-semibold">${s}</span>`
        ).join('');
        
        document.getElementById('missing-skills').innerHTML = data.missing_keywords.map(s => 
            `<span class="px-2.5 py-1 rounded-lg bg-error-500/10 border border-error-500/20 text-error-400 text-xs font-semibold">${s}</span>`
        ).join('');

        // Insights
        const insightsContainer = document.getElementById('insights-container');
        insightsContainer.innerHTML = data.insights.map(i => {
            const colors = {
                success: 'bg-success-500/5 border-success-500/10 text-success-400',
                warning: 'bg-warning-500/5 border-warning-500/10 text-warning-400',
                error: 'bg-error-500/5 border-error-500/10 text-error-400'
            };
            const icons = { success: '✓', warning: '!', error: '✕' };
            return `
                <div class="p-4 rounded-xl border flex gap-4 ${colors[i.type]}">
                    <div class="mt-0.5 font-bold">${icons[i.type]}</div>
                    <p class="text-sm font-medium leading-relaxed opacity-90">${i.text}</p>
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
                // Call our JS Generator
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

    // Handlers
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));
    
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-primary-500/40', 'bg-white/[0.04]');
    });
    
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-primary-500/40', 'bg-white/[0.04]');
    });
    
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-primary-500/40', 'bg-white/[0.04]');
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
