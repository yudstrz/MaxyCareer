import { jsPDF } from "jspdf";

export class CvGenerator {
    static async download(cvData) {
        const doc = new jsPDF();
        const margin = 20;
        let y = margin;
        const pageWidth = doc.internal.pageSize.getWidth();
        const contentWidth = pageWidth - (margin * 2);

        // Helper for text formatting and auto-paging
        const addText = (text, size = 10, style = 'normal', align = 'left', color = [0, 0, 0]) => {
            if (!text) return;
            doc.setFontSize(size);
            doc.setFont('helvetica', style);
            doc.setTextColor(color[0], color[1], color[2]);

            // Handle alignment
            let startX = margin;
            if (align === 'center') {
                const textWidth = doc.getStringUnitWidth(text) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                startX = (pageWidth - textWidth) / 2;
            } else if (align === 'right') {
                const textWidth = doc.getStringUnitWidth(text) * doc.internal.getFontSize() / doc.internal.scaleFactor;
                startX = pageWidth - margin - textWidth;
            }

            const lines = doc.splitTextToSize(text, contentWidth);
            lines.forEach(line => {
                if (y > 280) {
                    doc.addPage();
                    y = margin;
                }
                doc.text(line, startX, y);
                y += size * 0.4 + 2;
            });
        };

        const addHeading = (title) => {
            y += 4;
            addText(title.toUpperCase(), 11, 'bold');
            doc.setDrawColor(0);
            doc.setLineWidth(0.3);
            doc.line(margin, y - 2, pageWidth - margin, y - 2);
            y += 2;
        };

        // Header (Centered)
        addText(cvData.full_name, 16, 'bold', 'center');
        y += 1;
        const contactLine = `${cvData.contact.phone || ''} | ${cvData.contact.email} | ${cvData.contact.location || ''}`;
        addText(contactLine, 10, 'normal', 'center');
        if (cvData.contact.linkedin) {
            addText(`LinkedIn: ${cvData.contact.linkedin}`, 10, 'normal', 'center');
        }
        y += 4;

        // Summary
        if (cvData.summary) {
            addHeading('TENTANG SAYA');
            addText(cvData.summary, 10, 'normal', 'left');
        }

        // Education First (As per reference)
        if (cvData.education && cvData.education.length > 0) {
            addHeading('PENDIDIKAN');
            cvData.education.forEach(edu => {
                const startY = y;
                addText(edu.institution, 11, 'bold', 'left');
                const nextY = y;
                y = startY; // reset y to right-align the date on the same line
                addText(edu.year, 10, 'normal', 'right');
                y = nextY;

                addText(edu.degree, 10, 'normal', 'left');
                y += 2;
            });
        }

        // Experience
        if (cvData.experience && cvData.experience.length > 0) {
            addHeading('PENGALAMAN KERJA');
            cvData.experience.forEach(exp => {
                const startY = y;
                addText(`${exp.company} / ${exp.role}`, 11, 'bold', 'left');
                const nextY = y;
                y = startY; // reset y to right-align the date on the same line
                addText(exp.duration, 10, 'normal', 'right');
                y = nextY;

                exp.bullets.forEach(bullet => {
                    const bulletText = `•  ${bullet}`;
                    const bulletLines = doc.splitTextToSize(bulletText, contentWidth - 5);
                    bulletLines.forEach((line, index) => {
                        if (y > 280) { doc.addPage(); y = margin; }
                        // Indent wrapped lines
                        const indentX = index === 0 ? margin : margin + 5;
                        doc.text(line, indentX, y);
                        y += 10 * 0.4 + 2;
                    });
                });
                y += 3;
            });
        }

        // Skills
        if (cvData.skills && cvData.skills.length > 0) {
            addHeading('KEAHLIAN');
            addText(cvData.skills.join(', '), 10, 'normal', 'left');
        }

        doc.save(`${cvData.full_name.replace(/\s+/g, '_')}_ATS_Resume.pdf`);
    }
}
