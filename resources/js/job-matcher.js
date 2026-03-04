import { pipeline, env } from '@xenova/transformers';

// Configure transformers context
env.allowLocalModels = false;
env.useBrowserCache = true;

class JobMatcher {
    constructor() {
        this.modelId = 'Xenova/all-MiniLM-L6-v2';
        this.extractor = null;
        this.jobDatabase = null;
        this.jobIndex = null;
        this.isInitialized = false;
    }

    async init(progressCallback) {
        if (this.isInitialized) return;

        try {
            // 1. Load the AI pipeline
            progressCallback?.("Loading AI Model (this may take a minute on first load)...");
            this.extractor = await pipeline('feature-extraction', this.modelId, {
                quantized: true, // Use smaller, faster model
                progress_callback: (info) => {
                    if (info.status === 'downloading' && progressCallback) {
                        progressCallback(`Downloading model: ${info.file} (${Math.round(info.progress)}%)`);
                    }
                }
            });

            // 2. Load JSON datasets
            progressCallback?.("Loading Job Database...");
            const dbResponse = await fetch('/datasets/dtp_database.json');
            this.jobDatabase = await dbResponse.json();

            progressCallback?.("Loading Vector Index...");
            const idxResponse = await fetch('/datasets/pon_index.json');
            this.jobIndex = await idxResponse.json();

            this.isInitialized = true;
            progressCallback?.("Initialization complete.");
        } catch (error) {
            console.error("Failed to initialize JobMatcher:", error);
            throw new Error("Failed to load matching engine. Please check your internet connection and try again.");
        }
    }

    async generateEmbedding(text) {
        if (!this.extractor) throw new Error("Model not initialized");
        const output = await this.extractor(text, { pooling: 'mean', normalize: true });
        return Array.from(output.data);
    }

    cosineSimilarity(vecA, vecB) {
        let dotProduct = 0;
        let normA = 0;
        let normB = 0;
        for (let i = 0; i < vecA.length; i++) {
            dotProduct += vecA[i] * vecB[i];
            normA += vecA[i] * vecA[i];
            normB += vecB[i] * vecB[i];
        }
        return dotProduct / (Math.sqrt(normA) * Math.sqrt(normB));
    }

    async findMatches(cvText, topK = 5) {
        if (!this.isInitialized) {
            throw new Error("Job Matcher is not initialized. Call init() first.");
        }

        // 1. Generate text embedding
        const cvEmbedding = await this.generateEmbedding(cvText);

        // 2. Compute similarities
        const similarities = [];
        for (let i = 0; i < this.jobIndex.length; i++) {
            const jobVector = this.jobIndex[i];
            const similarity = this.cosineSimilarity(cvEmbedding, jobVector);
            similarities.push({ index: i, score: similarity });
        }

        // 3. Sort by descending similarity and get top K
        similarities.sort((a, b) => b.score - a.score);
        const topMatches = similarities.slice(0, topK);

        // 4. Map back to database records
        return topMatches.map(match => {
            const jobData = this.jobDatabase[match.index];
            return {
                ...jobData,
                matchScore: (match.score * 100).toFixed(1)
            };
        });
    }
}

// Expose the instance
window.JobMatcherEngine = new JobMatcher();
