#!/usr/bin/env python3

import sys
from collections import Counter
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt


def read_fasta(filepath):
    sequences = []
    current_seq = []

    with open(filepath, "r") as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            if line.startswith(">"):
                if current_seq:
                    sequences.append("".join(current_seq))
                    current_seq = []
            else:
                current_seq.append(line)

        if current_seq:
            sequences.append("".join(current_seq))

    return sequences


def calculate_conservation(sequences):
    if not sequences:
        raise ValueError("No sequences found in alignment file.")

    alignment_length = len(sequences[0])

    for seq in sequences:
        if len(seq) != alignment_length:
            raise ValueError("Sequences are not all the same length. Alignment appears invalid.")

    scores = []

    for i in range(alignment_length):
        column = [seq[i] for seq in sequences]
        residues = [aa for aa in column if aa != "-"]

        if not residues:
            scores.append(0.0)
            continue

        counts = Counter(residues)
        most_common_count = counts.most_common(1)[0][1]
        score = most_common_count / len(residues)
        scores.append(score)

    return scores


def main():
    if len(sys.argv) != 3:
        print("Usage: python3 conservation_plot.py input_alignment.fasta output_plot.png")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]

    sequences = read_fasta(input_file)
    scores = calculate_conservation(sequences)

    positions = list(range(1, len(scores) + 1))

    plt.figure(figsize=(12, 4))
    plt.plot(positions, scores)
    plt.xlabel("Alignment position")
    plt.ylabel("Conservation score")
    plt.title("Protein sequence conservation across alignment")
    plt.ylim(0, 1.05)
    plt.tight_layout()
    plt.savefig(output_file, dpi=150)


if __name__ == "__main__":
    main()