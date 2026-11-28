<?php

/*
| C# challenges. The format is described in javascript.php. The user writes
| a PascalCase method (TwoSum) inside a Solution class.
*/

return [
    [
        'slug' => 'single-number',
        'title' => 'Single Number',
        'difficulty' => 'easy',
        'summary' => 'Operações de bit: o XOR que cancela os pares.',
        'signature' => ['name' => 'singleNumber', 'params' => ['nums' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'Given a {{non-empty}} array of integers `nums`, every element appears twice {{except for}} one. Find that single one.',
            'You must implement a solution with linear runtime and only constant extra space.',
        ],
        'examples' => [
            ['input' => 'nums = [2, 2, 1]', 'output' => '1'],
            ['input' => 'nums = [4, 1, 2, 1, 2]', 'output' => '4'],
        ],
        'constraints' => ['1 <= nums.Length <= 3 * 10^4', '-3 * 10^4 <= nums[i] <= 3 * 10^4', 'Each element appears twice except for one element, which appears once.'],
        'glossary' => [
            'non-empty' => 'Não vazio: tem pelo menos um elemento.',
            'except for' => 'Com exceção de, menos.',
        ],
        'hints' => [
            'A HashSet works, but it uses O(n) space. Which operator makes a number cancel itself?',
            'With XOR (`^`), a ^ a == 0 and a ^ 0 == a. What is left after you XOR every number?',
        ],
        'tests' => [
            ['args' => [[2, 2, 1]], 'expected' => 1],
            ['args' => [[4, 1, 2, 1, 2]], 'expected' => 4],
            ['args' => [[1]], 'expected' => 1],
            ['args' => [[-3, 7, 7]], 'expected' => -3],
            ['args' => [[0, 5, 5, 9, 9]], 'expected' => 0],
        ],
        'explanation' => [
            'en' => 'I XOR all the numbers together. XOR of a number with itself is zero and XOR with zero keeps the number, and the order does not matter. So every pair cancels out and only the single number is left. It is O(n) time and O(1) space.',
            'pt' => 'Faço XOR de todos os números. O XOR de um número com ele mesmo dá zero, o XOR com zero mantém o número, e a ordem não importa. Então cada par se cancela e só sobra o número único. É O(n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            public class Solution
            {
                public int SingleNumber(int[] nums)
                {
                    var result = 0;
                    foreach (var num in nums)
                    {
                        result ^= num;
                    }
                    return result;
                }
            }
            CODE,
    ],
    [
        'slug' => 'pascals-triangle',
        'title' => 'Pascal\'s Triangle',
        'difficulty' => 'easy',
        'summary' => 'Arrays de arrays, montados linha a linha.',
        'signature' => ['name' => 'generate', 'params' => ['rows' => 'int'], 'returns' => 'int[][]'],
        'statement' => [
            'Given an integer `rows`, return the first `rows` rows of {{Pascal\'s triangle}}.',
            'Each row starts and ends with 1. Every other number is the {{sum}} of the two numbers directly above it.',
        ],
        'examples' => [
            ['input' => 'rows = 5', 'output' => '[[1], [1, 1], [1, 2, 1], [1, 3, 3, 1], [1, 4, 6, 4, 1]]'],
            ['input' => 'rows = 1', 'output' => '[[1]]'],
        ],
        'constraints' => ['1 <= rows <= 30'],
        'glossary' => [
            'Pascal\'s triangle' => 'Triângulo de Pascal: cada número é a soma dos dois de cima.',
            'sum' => 'Soma.',
        ],
        'hints' => [
            'Row i has i + 1 numbers. The first and the last are always 1.',
            'For the middle positions, use the previous row: row[j] = previous[j - 1] + previous[j].',
        ],
        'tests' => [
            ['args' => [1], 'expected' => [[1]]],
            ['args' => [2], 'expected' => [[1], [1, 1]]],
            ['args' => [5], 'expected' => [[1], [1, 1], [1, 2, 1], [1, 3, 3, 1], [1, 4, 6, 4, 1]]],
            ['args' => [7], 'expected' => [[1], [1, 1], [1, 2, 1], [1, 3, 3, 1], [1, 4, 6, 4, 1], [1, 5, 10, 10, 5, 1], [1, 6, 15, 20, 15, 6, 1]]],
        ],
        'explanation' => [
            'en' => 'I build the triangle row by row. Each row starts and ends with one, and each position in between is the sum of the two positions above it in the previous row. The output has about n squared over two numbers, so it is O(n²) in time and space, which is the size of the answer itself.',
            'pt' => 'Monto o triângulo linha por linha. Cada linha começa e termina com um, e cada posição no meio é a soma das duas posições de cima, na linha anterior. A saída tem cerca de n ao quadrado sobre dois números, então é O(n²) em tempo e espaço, que é o tamanho da própria resposta.',
        ],
        'solution' => <<<'CODE'
            public class Solution
            {
                public int[][] Generate(int rows)
                {
                    var triangle = new int[rows][];
                    for (var i = 0; i < rows; i++)
                    {
                        triangle[i] = new int[i + 1];
                        triangle[i][0] = 1;
                        triangle[i][i] = 1;
                        for (var j = 1; j < i; j++)
                        {
                            triangle[i][j] = triangle[i - 1][j - 1] + triangle[i - 1][j];
                        }
                    }
                    return triangle;
                }
            }
            CODE,
    ],
    [
        'slug' => 'intersection-of-two-arrays',
        'title' => 'Intersection of Two Arrays',
        'difficulty' => 'easy',
        'summary' => 'HashSet e LINQ para cruzar duas listas.',
        'signature' => ['name' => 'intersection', 'params' => ['nums1' => 'int[]', 'nums2' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'Given two integer arrays `nums1` and `nums2`, return an array of their {{intersection}}.',
            'Each element in the result must be unique. Return the result sorted in {{ascending order}}.',
        ],
        'examples' => [
            ['input' => 'nums1 = [1, 2, 2, 1], nums2 = [2, 2]', 'output' => '[2]'],
            ['input' => 'nums1 = [4, 9, 5], nums2 = [9, 4, 9, 8, 4]', 'output' => '[4, 9]'],
        ],
        'constraints' => ['1 <= nums1.Length, nums2.Length <= 1000', '0 <= nums1[i], nums2[i] <= 1000'],
        'glossary' => [
            'intersection' => 'Interseção: os elementos que estão nos dois arrays.',
            'ascending order' => 'Ordem crescente, do menor para o maior.',
        ],
        'hints' => [
            'Put the first array in a HashSet. Which numbers of the second array are in it?',
            'Use another set for the result, so each number appears once, and sort it at the end.',
        ],
        'tests' => [
            ['args' => [[1, 2, 2, 1], [2, 2]], 'expected' => [2]],
            ['args' => [[4, 9, 5], [9, 4, 9, 8, 4]], 'expected' => [4, 9]],
            ['args' => [[1, 2, 3], [4, 5]], 'expected' => []],
            ['args' => [[7, 3, 1], [1, 3, 7, 7]], 'expected' => [1, 3, 7]],
        ],
        'explanation' => [
            'en' => 'I put the first array in a HashSet, so each lookup is O(1) on average. Then I go through the second array and add every number that is in the first set to a result set, which removes the duplicates. Finally I sort the result. It is O(n + m) for the sets plus O(k log k) to sort the k common numbers.',
            'pt' => 'Coloco o primeiro array num HashSet, para cada consulta ser O(1) em média. Depois percorro o segundo array e adiciono cada número que está no primeiro conjunto a um conjunto de resultado, o que remove as repetições. No fim, ordeno o resultado. É O(n + m) para os conjuntos mais O(k log k) para ordenar os k números em comum.',
        ],
        'solution' => <<<'CODE'
            using System.Collections.Generic;
            using System.Linq;

            public class Solution
            {
                public int[] Intersection(int[] nums1, int[] nums2)
                {
                    var first = new HashSet<int>(nums1);
                    var common = new HashSet<int>();
                    foreach (var num in nums2)
                    {
                        if (first.Contains(num))
                        {
                            common.Add(num);
                        }
                    }
                    return common.OrderBy(num => num).ToArray();
                }
            }
            CODE,
    ],
    [
        'slug' => 'is-subsequence',
        'title' => 'Is Subsequence',
        'difficulty' => 'easy',
        'summary' => 'Dois ponteiros em duas strings diferentes.',
        'signature' => ['name' => 'isSubsequence', 'params' => ['s' => 'string', 't' => 'string'], 'returns' => 'bool'],
        'statement' => [
            'Given two strings `s` and `t`, return `true` if `s` is a {{subsequence}} of `t`, or `false` otherwise.',
            'A subsequence is a new string formed from the original string by deleting some (or none) of the characters, without {{disturbing}} the relative positions of the remaining characters. For example, "ace" is a subsequence of "abcde", but "aec" is not.',
        ],
        'examples' => [
            ['input' => 's = "abc", t = "ahbgdc"', 'output' => 'true'],
            ['input' => 's = "axc", t = "ahbgdc"', 'output' => 'false'],
        ],
        'constraints' => ['0 <= s.Length <= 100', '0 <= t.Length <= 10^4', '`s` and `t` consist of lowercase English letters.'],
        'glossary' => [
            'subsequence' => 'Subsequência: caracteres na mesma ordem, podendo pular alguns.',
            'disturbing' => 'Alterar, bagunçar (disturb = perturbar).',
        ],
        'hints' => [
            'Walk through `t` once. When the current character matches the next character of `s`, move forward in `s`.',
            'If you reach the end of `s`, every character was found in order. Is an empty `s` a subsequence?',
        ],
        'tests' => [
            ['args' => ['abc', 'ahbgdc'], 'expected' => true],
            ['args' => ['axc', 'ahbgdc'], 'expected' => false],
            ['args' => ['', 'ahbgdc'], 'expected' => true],
            ['args' => ['aec', 'abcde'], 'expected' => false],
            ['args' => ['abc', ''], 'expected' => false],
            ['args' => ['aaa', 'aa'], 'expected' => false],
        ],
        'explanation' => [
            'en' => 'I use two pointers, one in each string. I walk through t, and whenever its character matches the current character of s, I move the pointer in s forward. If the pointer reaches the end of s, all its characters appeared in order. It is O(m) time, where m is the length of t, and O(1) space.',
            'pt' => 'Uso dois ponteiros, um em cada string. Percorro t e, sempre que o caractere dela bate com o caractere atual de s, avanço o ponteiro de s. Se o ponteiro chega ao fim de s, todos os caracteres apareceram em ordem. É O(m) em tempo, sendo m o tamanho de t, e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            public class Solution
            {
                public bool IsSubsequence(string s, string t)
                {
                    var found = 0;
                    foreach (var c in t)
                    {
                        if (found < s.Length && s[found] == c)
                        {
                            found++;
                        }
                    }
                    return found == s.Length;
                }
            }
            CODE,
    ],
    [
        'slug' => 'three-sum',
        'title' => '3Sum',
        'difficulty' => 'medium',
        'summary' => 'Ordenação mais dois ponteiros, sem triplas repetidas.',
        'signature' => ['name' => 'threeSum', 'params' => ['nums' => 'int[]'], 'returns' => 'int[][]'],
        'statement' => [
            'Given an integer array `nums`, return all the {{triplets}} `[nums[i], nums[j], nums[k]]` such that `i`, `j` and `k` are different positions and `nums[i] + nums[j] + nums[k] == 0`.',
            'The result must not contain {{duplicate}} triplets. Write each triplet in ascending order and sort the list of triplets in ascending order too, comparing the first number, then the second.',
        ],
        'examples' => [
            ['input' => 'nums = [-1, 0, 1, 2, -1, -4]', 'output' => '[[-1, -1, 2], [-1, 0, 1]]'],
            ['input' => 'nums = [0, 1, 1]', 'output' => '[]', 'explanation' => 'The only possible triplet does not sum up to 0.'],
        ],
        'constraints' => ['3 <= nums.Length <= 3000', '-10^5 <= nums[i] <= 10^5'],
        'glossary' => [
            'triplets' => 'Triplas, trios de números.',
            'duplicate' => 'Repetido, duplicado.',
        ],
        'hints' => [
            'Sort the array first. For each number, the other two must add up to its negative.',
            'With the rest sorted, two pointers find the pairs. Skip equal values to avoid duplicate triplets.',
        ],
        'tests' => [
            ['args' => [[-1, 0, 1, 2, -1, -4]], 'expected' => [[-1, -1, 2], [-1, 0, 1]]],
            ['args' => [[0, 1, 1]], 'expected' => []],
            ['args' => [[0, 0, 0]], 'expected' => [[0, 0, 0]]],
            ['args' => [[0, 0, 0, 0]], 'expected' => [[0, 0, 0]]],
            ['args' => [[-2, 0, 1, 1, 2]], 'expected' => [[-2, 0, 2], [-2, 1, 1]]],
            ['args' => [[3, -2, 1, 0]], 'expected' => []],
        ],
        'explanation' => [
            'en' => 'I sort the array. For each position i, I look for two numbers after it that add up to minus nums[i], using two pointers from both ends of the rest. If the sum is too small I move the left pointer, if it is too large I move the right one. I skip repeated values so no triplet appears twice. Sorting is O(n log n) and the search is O(n²).',
            'pt' => 'Ordeno o array. Para cada posição i, procuro dois números depois dela que somem menos nums[i], com dois ponteiros nas pontas do restante. Se a soma é pequena demais, movo o ponteiro da esquerda; se é grande demais, o da direita. Pulo valores repetidos para nenhuma tripla aparecer duas vezes. Ordenar é O(n log n) e a busca é O(n²).',
        ],
        'solution' => <<<'CODE'
            using System;
            using System.Collections.Generic;

            public class Solution
            {
                public int[][] ThreeSum(int[] nums)
                {
                    var sorted = (int[]) nums.Clone();
                    Array.Sort(sorted);
                    var result = new List<int[]>();
                    for (var i = 0; i < sorted.Length - 2; i++)
                    {
                        if (i > 0 && sorted[i] == sorted[i - 1])
                        {
                            continue;
                        }
                        int left = i + 1, right = sorted.Length - 1;
                        while (left < right)
                        {
                            var sum = sorted[i] + sorted[left] + sorted[right];
                            if (sum < 0)
                            {
                                left++;
                            }
                            else if (sum > 0)
                            {
                                right--;
                            }
                            else
                            {
                                result.Add(new[] { sorted[i], sorted[left], sorted[right] });
                                while (left < right && sorted[left] == sorted[left + 1]) left++;
                                while (left < right && sorted[right] == sorted[right - 1]) right--;
                                left++;
                                right--;
                            }
                        }
                    }
                    return result.ToArray();
                }
            }
            CODE,
    ],
    [
        'slug' => 'spiral-matrix',
        'title' => 'Spiral Matrix',
        'difficulty' => 'medium',
        'summary' => 'Matrizes e limites que encolhem a cada volta.',
        'signature' => ['name' => 'spiralOrder', 'params' => ['matrix' => 'int[][]'], 'returns' => 'int[]'],
        'statement' => [
            'Given an `m x n` {{matrix}}, return all the elements of the matrix in {{spiral order}}.',
            'Start at the top-left corner and go right, then down, then left, then up, and keep turning {{clockwise}} toward the center.',
        ],
        'examples' => [
            ['input' => 'matrix = [[1, 2, 3], [4, 5, 6], [7, 8, 9]]', 'output' => '[1, 2, 3, 6, 9, 8, 7, 4, 5]'],
            ['input' => 'matrix = [[1, 2, 3, 4], [5, 6, 7, 8], [9, 10, 11, 12]]', 'output' => '[1, 2, 3, 4, 8, 12, 11, 10, 9, 5, 6, 7]'],
        ],
        'constraints' => ['1 <= m, n <= 10', '-100 <= matrix[i][j] <= 100'],
        'glossary' => [
            'matrix' => 'Matriz: uma tabela de linhas e colunas.',
            'spiral order' => 'Ordem em espiral: dando voltas de fora para dentro.',
            'clockwise' => 'No sentido horário.',
        ],
        'hints' => [
            'Keep four limits: top, bottom, left and right. Each side you walk moves one limit inward.',
            'After walking the top row and the right column, check that a row and a column are still left before walking back.',
        ],
        'tests' => [
            ['args' => [[[1, 2, 3], [4, 5, 6], [7, 8, 9]]], 'expected' => [1, 2, 3, 6, 9, 8, 7, 4, 5]],
            ['args' => [[[1, 2, 3, 4], [5, 6, 7, 8], [9, 10, 11, 12]]], 'expected' => [1, 2, 3, 4, 8, 12, 11, 10, 9, 5, 6, 7]],
            ['args' => [[[7]]], 'expected' => [7]],
            ['args' => [[[1, 2, 3]]], 'expected' => [1, 2, 3]],
            ['args' => [[[1], [2], [3]]], 'expected' => [1, 2, 3]],
            ['args' => [[[1, 2], [3, 4], [5, 6]]], 'expected' => [1, 2, 4, 6, 5, 3]],
        ],
        'explanation' => [
            'en' => 'I keep four boundaries: top, bottom, left and right. I walk the top row, then the right column, then the bottom row backwards and the left column upwards, moving each boundary inward after I walk it. Before the last two sides I check that the boundaries have not crossed, which handles single rows and columns. Every element is visited once, so it is O(m times n).',
            'pt' => 'Mantenho quatro limites: topo, base, esquerda e direita. Percorro a linha do topo, depois a coluna da direita, depois a linha da base de trás para frente e a coluna da esquerda de baixo para cima, movendo cada limite para dentro depois de percorrê-lo. Antes dos dois últimos lados confiro se os limites não se cruzaram, o que trata linhas e colunas únicas. Cada elemento é visitado uma vez, então é O(m vezes n).',
        ],
        'solution' => <<<'CODE'
            using System.Collections.Generic;

            public class Solution
            {
                public int[] SpiralOrder(int[][] matrix)
                {
                    var result = new List<int>();
                    int top = 0, bottom = matrix.Length - 1, left = 0, right = matrix[0].Length - 1;
                    while (top <= bottom && left <= right)
                    {
                        for (var j = left; j <= right; j++) result.Add(matrix[top][j]);
                        top++;
                        for (var i = top; i <= bottom; i++) result.Add(matrix[i][right]);
                        right--;
                        if (top <= bottom)
                        {
                            for (var j = right; j >= left; j--) result.Add(matrix[bottom][j]);
                            bottom--;
                        }
                        if (left <= right)
                        {
                            for (var i = bottom; i >= top; i--) result.Add(matrix[i][left]);
                            left++;
                        }
                    }
                    return result.ToArray();
                }
            }
            CODE,
    ],
];
