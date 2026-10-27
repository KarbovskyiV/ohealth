#!/usr/bin/env python3
"""Extract numbered clauses with physical PDF pages; do not paraphrase requirements."""
import argparse
import hashlib
import json
import re
from pathlib import Path
from pypdf import PdfReader


def extract_sections(pages, start_page):
    sections = []
    current = None
    seen = set()
    for page_number, page in enumerate(pages, 1):
        if page_number < start_page:
            continue
        for raw_line in page.splitlines():
            line = re.sub(r'\s+', ' ', raw_line).strip()
            if not line or re.fullmatch(r'\d+', line):
                continue
            # In this layout, paragraph headings are indented; unindented wrapped
            # references such as "п.\n3.6.1.4.4 ..." are body text, not new clauses.
            match = re.match(r'^ {3,12}([123](?:\.\d+){1,7})\.?\s+(.+)', raw_line)
            if match:
                clause = match.group(1)
                if clause in seen:
                    raise ValueError(f'Duplicate clause {clause} on page {page_number}; inspect the PDF before publishing.')
                seen.add(clause)
                current = {'id': clause, 'pageStart': page_number, 'pageEnd': page_number, 'text': line}
                sections.append(current)
            elif current is not None:
                current['text'] += '\n' + line
                current['pageEnd'] = page_number
    if not sections:
        raise ValueError('No numbered requirements found; OCR or extraction needs manual review.')
    return sections


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('pdf', type=Path)
    parser.add_argument('output', type=Path)
    parser.add_argument('--edition', required=True)
    parser.add_argument('--order', required=True)
    parser.add_argument('--start-page', type=int, required=True, help='First body PDF page, excluding contents/definitions')
    args = parser.parse_args()
    reader = PdfReader(args.pdf)
    sections = extract_sections([p.extract_text(extraction_mode='layout') for p in reader.pages], args.start_page)
    result = {'schemaVersion': 1, 'edition': args.edition, 'order': args.order,
              'sourceFile': args.pdf.name, 'sourceSha256': hashlib.sha256(args.pdf.read_bytes()).hexdigest(),
              'pdfPages': len(reader.pages), 'extraction': 'pypdf layout; whitespace normalized; physical PDF pages',
              'sections': sections}
    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'Extracted {len(sections)} numbered clauses from {len(reader.pages)} PDF pages.')


if __name__ == '__main__':
    main()
