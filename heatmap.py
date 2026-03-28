import sys
import numpy as np
import pandas as pd
import seaborn as sns
import matplotlib.pyplot as plt
import re

if len(sys.argv) != 3:
    print("Usage: python3 heatmap.py input_alignment.fasta output.png")
    sys.exit(1)

input_file = sys.argv[1]
output_file = sys.argv[2]

def read_fasta(filepath):
    sequences = []
    names = []
    seq = ""

    with open(filepath) as f:
        for line in f:
            line = line.strip()
            if not line:
                continue
            if line.startswith(">"):
                if seq:
                    sequences.append(seq)
                    seq = ""
                names.append(line[1:])
            else:
                seq += line
        if seq:
            sequences.append(seq)

    return names, sequences


def identity(seq1, seq2):
    matches = 0
    total = len(seq1)

    for a, b in zip(seq1, seq2):
        if a == b:
            matches += 1

    return matches / total


names, sequences = read_fasta(input_file)
n = len(sequences)
matrix = np.zeros((n, n))

for i in range(n):
    for j in range(n):
        matrix[i][j] = identity(sequences[i], sequences[j])

# Shorten labels
def make_species_label(header):
    match = re.search(r'\[([^\]]+)\]', header)
    if match:
        species = match.group(1).strip().split()
        if len(species) >= 2:
            return f"{species[0][0]}_{species[1][:10]}"
        return species[0][:12]
    return header.split()[0][:12]

short_names = [make_species_label(name) for name in names]

df = pd.DataFrame(matrix, index=short_names, columns=short_names)

g = sns.clustermap(
    df,
    figsize=(12, 12),
    cmap="viridis",
    xticklabels=True,
    yticklabels=True
)

# Show fewer labels
step = max(1, n // 12)

for i, label in enumerate(g.ax_heatmap.get_xticklabels()):
    if i % step != 0:
        label.set_visible(False)

for i, label in enumerate(g.ax_heatmap.get_yticklabels()):
    if i % step != 0:
        label.set_visible(False)

plt.setp(g.ax_heatmap.get_xticklabels(), rotation=90, fontsize=8)
plt.setp(g.ax_heatmap.get_yticklabels(), fontsize=8)

g.ax_heatmap.set_title("Clustered Pairwise Sequence Identity Heatmap", pad=20)

plt.savefig(output_file, dpi=150, bbox_inches="tight")