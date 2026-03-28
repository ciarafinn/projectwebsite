#!/usr/bin/env python3

import sys
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt


def read_fasta_lengths(filepath):
    lengths = []
    current = []

    with open(filepath) as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            if line.startswith(">"):
                if current:
                    lengths.append(len("".join(current)))
                    current = []
            else:
                current.append(line)

        if current:
            lengths.append(len("".join(current)))

    return lengths


def main():
    if len(sys.argv) != 3:
        print("Usage: python3 length_plot.py input.fasta output.png")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]

    lengths = read_fasta_lengths(input_file)

    plt.figure(figsize=(8, 4))
    plt.hist(lengths, bins=10)
    plt.xlabel("Protein length (aa)")
    plt.ylabel("Number of sequences")
    plt.title("Distribution of protein sequence lengths")
    plt.tight_layout()
    plt.savefig(output_file, dpi=150)


if __name__ == "__main__":
    main()