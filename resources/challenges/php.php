<?php

/*
| PHP challenges. The format is described in javascript.php. The user writes
| a function in solution.php; strings are byte strings (ASCII in every test).
*/

return [
    [
        'slug' => 'string-compression',
        'title' => 'String Compression',
        'difficulty' => 'easy',
        'summary' => 'Percorrer uma string contando repetições seguidas.',
        'signature' => ['name' => 'compress', 'params' => ['s' => 'string'], 'returns' => 'string'],
        'statement' => [
            'Implement a method to perform basic string compression using the counts of repeated characters. For example, the string "aabcccccaaa" would become "a2b1c5a3".',
            'If the {{compressed}} string would not become {{shorter}} than the original string, return the original string. You can assume the string has only uppercase and lowercase letters.',
        ],
        'examples' => [
            ['input' => '$s = "aabcccccaaa"', 'output' => '"a2b1c5a3"'],
            ['input' => '$s = "abc"', 'output' => '"abc"', 'explanation' => '"a1b1c1" is longer than "abc", so the original string is returned.'],
        ],
        'constraints' => ['0 <= strlen($s) <= 10^4', '`$s` consists of English letters.'],
        'glossary' => [
            'compressed' => 'Comprimido, compactado.',
            'shorter' => 'Mais curto (comparativo de "short").',
        ],
        'hints' => [
            'Walk through the string and count how many times the current character repeats in a row.',
            'Build the result in an array of pieces and join it at the end. Then compare the lengths.',
        ],
        'tests' => [
            ['args' => ['aabcccccaaa'], 'expected' => 'a2b1c5a3'],
            ['args' => ['abc'], 'expected' => 'abc'],
            ['args' => [''], 'expected' => ''],
            ['args' => ['aa'], 'expected' => 'aa'],
            ['args' => ['aaa'], 'expected' => 'a3'],
            ['args' => ['AAaaBB'], 'expected' => 'AAaaBB'],
            ['args' => ['zzzzzzzzzzzzb'], 'expected' => 'z12b1'],
        ],
        'explanation' => [
            'en' => 'I walk through the string counting how many times the current character repeats in a row. When the character changes, I add the character and its count to a list of pieces. At the end I join the pieces and return the compressed string only if it is shorter. It is O(n) time and O(n) space.',
            'pt' => 'Percorro a string contando quantas vezes o caractere atual se repete em sequência. Quando o caractere muda, adiciono o caractere e a contagem a uma lista de pedaços. No fim, junto os pedaços e só devolvo a string comprimida se ela for mais curta. É O(n) em tempo e O(n) em espaço.',
        ],
        'solution' => <<<'CODE'
            <?php

            function compress(string $s): string
            {
                $pieces = [];
                $length = strlen($s);
                for ($i = 0; $i < $length; $i = $j) {
                    $j = $i;
                    while ($j < $length && $s[$j] === $s[$i]) {
                        $j++;
                    }
                    $pieces[] = $s[$i].($j - $i);
                }
                $compressed = implode('', $pieces);

                return strlen($compressed) < $length ? $compressed : $s;
            }
            CODE,
    ],
    [
        'slug' => 'isomorphic-strings',
        'title' => 'Isomorphic Strings',
        'difficulty' => 'easy',
        'summary' => 'Dois mapas para garantir uma troca de letras um para um.',
        'signature' => ['name' => 'isIsomorphic', 'params' => ['s' => 'string', 't' => 'string'], 'returns' => 'bool'],
        'statement' => [
            'Given two strings `$s` and `$t`, determine if they are {{isomorphic}}. Two strings are isomorphic if the characters in `$s` can be replaced to get `$t`.',
            'All {{occurrences}} of a character must be replaced with the same character, keeping the order. No two characters may map to the same character, but a character may map to itself.',
        ],
        'examples' => [
            ['input' => '$s = "egg", $t = "add"', 'output' => 'true', 'explanation' => 'e becomes a and g becomes d.'],
            ['input' => '$s = "badc", $t = "baba"', 'output' => 'false', 'explanation' => 'b and d would both have to become b.'],
        ],
        'constraints' => ['1 <= strlen($s) <= 5 * 10^4', 'strlen($t) == strlen($s)', '`$s` and `$t` consist of ASCII characters.'],
        'glossary' => [
            'isomorphic' => 'Isomorfas: com a mesma "forma", trocando letra por letra.',
            'occurrences' => 'Ocorrências: cada vez que o caractere aparece.',
        ],
        'hints' => [
            'Keep an array from each character of `$s` to the character of `$t` it became.',
            'One map is not enough: "ab" and "aa" pass it. What does a second map, from `$t` back to `$s`, catch?',
        ],
        'tests' => [
            ['args' => ['egg', 'add'], 'expected' => true],
            ['args' => ['foo', 'bar'], 'expected' => false],
            ['args' => ['paper', 'title'], 'expected' => true],
            ['args' => ['badc', 'baba'], 'expected' => false],
            ['args' => ['ab', 'aa'], 'expected' => false],
            ['args' => ['a', 'a'], 'expected' => true],
        ],
        'explanation' => [
            'en' => 'I keep two maps: one from the characters of s to the characters of t, and one in the other direction. For each position, if either map already has a different partner for the character, the mapping is not one-to-one and I return false. Otherwise I record the pair. It is O(n) time, and the maps hold at most the alphabet size.',
            'pt' => 'Mantenho dois mapas: um dos caracteres de s para os de t e outro na direção contrária. Para cada posição, se algum mapa já tem outro par para o caractere, a troca não é um para um e devolvo false. Senão, guardo o par. É O(n) em tempo, e os mapas guardam no máximo o tamanho do alfabeto.',
        ],
        'solution' => <<<'CODE'
            <?php

            function isIsomorphic(string $s, string $t): bool
            {
                $forward = [];
                $backward = [];
                for ($i = 0; $i < strlen($s); $i++) {
                    $a = $s[$i];
                    $b = $t[$i];
                    if (($forward[$a] ?? $b) !== $b || ($backward[$b] ?? $a) !== $a) {
                        return false;
                    }
                    $forward[$a] = $b;
                    $backward[$b] = $a;
                }

                return true;
            }
            CODE,
    ],
    [
        'slug' => 'happy-number',
        'title' => 'Happy Number',
        'difficulty' => 'easy',
        'summary' => 'Detectar um ciclo guardando o que já foi visto.',
        'signature' => ['name' => 'isHappy', 'params' => ['n' => 'int'], 'returns' => 'bool'],
        'statement' => [
            'A {{happy number}} is defined by this process: starting with a positive integer, replace the number by the sum of the squares of its digits. Repeat until the number equals 1, or until it {{loops endlessly}} in a cycle that does not include 1.',
            'Those numbers for which this process ends in 1 are happy. Given `$n`, return `true` if it is a happy number, and `false` if it is not.',
        ],
        'examples' => [
            ['input' => '$n = 19', 'output' => 'true', 'explanation' => '1² + 9² = 82, 8² + 2² = 68, 6² + 8² = 100, 1² + 0² + 0² = 1.'],
            ['input' => '$n = 2', 'output' => 'false'],
        ],
        'constraints' => ['1 <= $n <= 2^31 - 1'],
        'glossary' => [
            'happy number' => 'Número feliz: o processo termina em 1.',
            'loops endlessly' => 'Fica em um laço sem fim, repetindo os mesmos números.',
        ],
        'hints' => [
            'Write a helper that returns the sum of the squares of the digits, using `% 10` and `intdiv`.',
            'If a number shows up twice, you are in a cycle. How can you remember the numbers you have already seen?',
        ],
        'tests' => [
            ['args' => [19], 'expected' => true],
            ['args' => [2], 'expected' => false],
            ['args' => [1], 'expected' => true],
            ['args' => [7], 'expected' => true],
            ['args' => [4], 'expected' => false],
            ['args' => [2147483647], 'expected' => false],
        ],
        'explanation' => [
            'en' => 'I repeat the process and keep every number I have seen in an array used as a set. If I reach one, the number is happy. If a number appears a second time, the process is in a cycle without one, so it is not happy. The numbers quickly become small, so the loop ends after a few steps.',
            'pt' => 'Repito o processo e guardo cada número visto num array usado como conjunto. Se chego a um, o número é feliz. Se um número aparece pela segunda vez, o processo está num ciclo sem o um, então não é feliz. Os números ficam pequenos rapidamente, então o laço termina em poucos passos.',
        ],
        'solution' => <<<'CODE'
            <?php

            function isHappy(int $n): bool
            {
                $seen = [];
                while ($n !== 1 && ! isset($seen[$n])) {
                    $seen[$n] = true;
                    $sum = 0;
                    while ($n > 0) {
                        $digit = $n % 10;
                        $sum += $digit * $digit;
                        $n = intdiv($n, 10);
                    }
                    $n = $sum;
                }

                return $n === 1;
            }
            CODE,
    ],
    [
        'slug' => 'sort-colors',
        'title' => 'Sort Colors',
        'difficulty' => 'medium',
        'summary' => 'A bandeira holandesa: três ponteiros, uma passada.',
        'signature' => ['name' => 'sortColors', 'params' => ['nums' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'Given an array `$nums` with objects colored red, white or blue, sort them so that objects of the same color are {{adjacent}}, in the order red, white and blue. The colors are the integers 0, 1 and 2.',
            'Solve it without the library\'s sort function, in a single pass if you can, and return the array.',
        ],
        'examples' => [
            ['input' => '$nums = [2, 0, 2, 1, 1, 0]', 'output' => '[0, 0, 1, 1, 2, 2]'],
            ['input' => '$nums = [2, 0, 1]', 'output' => '[0, 1, 2]'],
        ],
        'constraints' => ['1 <= count($nums) <= 300', '`$nums[i]` is 0, 1 or 2.'],
        'glossary' => [
            'adjacent' => 'Adjacentes: um ao lado do outro.',
        ],
        'hints' => [
            'Counting the 0s, 1s and 2s and rewriting the array takes two passes. Can you do one?',
            'Keep three pointers: where the next 0 goes, the current element, and where the next 2 goes. Swap as you walk.',
        ],
        'tests' => [
            ['args' => [[2, 0, 2, 1, 1, 0]], 'expected' => [0, 0, 1, 1, 2, 2]],
            ['args' => [[2, 0, 1]], 'expected' => [0, 1, 2]],
            ['args' => [[0]], 'expected' => [0]],
            ['args' => [[1, 1, 1]], 'expected' => [1, 1, 1]],
            ['args' => [[2, 2, 0, 0]], 'expected' => [0, 0, 2, 2]],
        ],
        'explanation' => [
            'en' => 'I use the Dutch national flag algorithm with three pointers: low marks where the next zero goes, high where the next two goes, and mid walks through the array. A zero is swapped to low, a two is swapped to high, and a one just moves on. After a swap with high I check mid again, because the new value has not been seen. One pass, O(n) time and O(1) space.',
            'pt' => 'Uso o algoritmo da bandeira holandesa com três ponteiros: low marca onde vai o próximo zero, high onde vai o próximo dois, e mid percorre o array. Um zero é trocado para low, um dois é trocado para high e um um só segue. Depois de trocar com high confiro mid de novo, porque o valor novo ainda não foi visto. Uma passada, O(n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            <?php

            function sortColors(array $nums): array
            {
                [$low, $mid, $high] = [0, 0, count($nums) - 1];
                while ($mid <= $high) {
                    if ($nums[$mid] === 0) {
                        [$nums[$low], $nums[$mid]] = [$nums[$mid], $nums[$low]];
                        $low++;
                        $mid++;
                    } elseif ($nums[$mid] === 2) {
                        [$nums[$mid], $nums[$high]] = [$nums[$high], $nums[$mid]];
                        $high--;
                    } else {
                        $mid++;
                    }
                }

                return $nums;
            }
            CODE,
    ],
    [
        'slug' => 'kth-largest-element',
        'title' => 'Kth Largest Element in an Array',
        'difficulty' => 'medium',
        'summary' => 'Heap com SplMinHeap: só os k maiores ficam guardados.',
        'signature' => ['name' => 'findKthLargest', 'params' => ['nums' => 'int[]', 'k' => 'int'], 'returns' => 'int'],
        'statement' => [
            'Given an integer array `$nums` and an integer `$k`, return the k-th largest element in the array.',
            'Note that it is the k-th largest element in sorted order, not the k-th {{distinct}} element. Sorting works; can you do better with a {{heap}}?',
        ],
        'examples' => [
            ['input' => '$nums = [3, 2, 1, 5, 6, 4], $k = 2', 'output' => '5'],
            ['input' => '$nums = [3, 2, 3, 1, 2, 4, 5, 5, 6], $k = 4', 'output' => '4', 'explanation' => 'Sorted from largest: 6, 5, 5, 4. Equal values count separately.'],
        ],
        'constraints' => ['1 <= $k <= count($nums) <= 10^5', '-10^4 <= $nums[i] <= 10^4'],
        'glossary' => [
            'distinct' => 'Distinto, diferente dos outros.',
            'heap' => 'Heap: estrutura que sempre deixa o menor (ou o maior) elemento no topo.',
        ],
        'hints' => [
            'A min-heap of size k keeps the k largest elements seen so far. Where is the k-th largest?',
            'PHP has `SplMinHeap`: `insert()`, `extract()`, `top()` and `count()`.',
        ],
        'tests' => [
            ['args' => [[3, 2, 1, 5, 6, 4], 2], 'expected' => 5],
            ['args' => [[3, 2, 3, 1, 2, 4, 5, 5, 6], 4], 'expected' => 4],
            ['args' => [[1], 1], 'expected' => 1],
            ['args' => [[7, 7, 7], 2], 'expected' => 7],
            ['args' => [[-1, -5, 3, 0], 4], 'expected' => -5],
        ],
        'explanation' => [
            'en' => 'I keep a min-heap with at most k elements. I insert each number, and when the heap grows beyond k I remove its smallest element. At the end, the heap holds the k largest numbers and its top, the smallest of them, is the k-th largest. It is O(n log k) time and O(k) space, better than sorting when k is small.',
            'pt' => 'Mantenho um min-heap com no máximo k elementos. Insiro cada número e, quando o heap passa de k, removo o menor. No fim, o heap guarda os k maiores números e o topo, o menor deles, é o k-ésimo maior. É O(n log k) em tempo e O(k) em espaço, melhor que ordenar quando k é pequeno.',
        ],
        'solution' => <<<'CODE'
            <?php

            function findKthLargest(array $nums, int $k): int
            {
                $heap = new SplMinHeap();
                foreach ($nums as $num) {
                    $heap->insert($num);
                    if ($heap->count() > $k) {
                        $heap->extract();
                    }
                }

                return $heap->top();
            }
            CODE,
    ],
    [
        'slug' => 'coin-change',
        'title' => 'Coin Change',
        'difficulty' => 'medium',
        'summary' => 'Programação dinâmica clássica, de baixo para cima.',
        'signature' => ['name' => 'coinChange', 'params' => ['coins' => 'int[]', 'amount' => 'int'], 'returns' => 'int'],
        'statement' => [
            'You are given an integer array `$coins` with coins of different {{denominations}} and an integer `$amount` with a total amount of money.',
            'Return the {{fewest}} number of coins that you need to make up that amount. If that amount cannot be made up by any combination of the coins, return `-1`. You have an {{unlimited}} number of each kind of coin.',
        ],
        'examples' => [
            ['input' => '$coins = [1, 2, 5], $amount = 11', 'output' => '3', 'explanation' => '11 = 5 + 5 + 1.'],
            ['input' => '$coins = [2], $amount = 3', 'output' => '-1'],
        ],
        'constraints' => ['1 <= count($coins) <= 12', '1 <= $coins[i] <= 2^31 - 1', '0 <= $amount <= 10^4'],
        'glossary' => [
            'denominations' => 'Valores de face das moedas (denomination = valor da moeda ou nota).',
            'fewest' => 'O menor número (superlativo de "few", para coisas contáveis).',
            'unlimited' => 'Ilimitado, sem limite.',
        ],
        'hints' => [
            'A greedy choice (always the largest coin) fails for coins [1, 3, 4] and amount 6. Why?',
            'Let best[x] be the fewest coins for amount x. Then best[x] = 1 + the minimum of best[x - coin] over every coin.',
        ],
        'tests' => [
            ['args' => [[1, 2, 5], 11], 'expected' => 3],
            ['args' => [[2], 3], 'expected' => -1],
            ['args' => [[1], 0], 'expected' => 0],
            ['args' => [[1, 3, 4], 6], 'expected' => 2],
            ['args' => [[186, 419, 83, 408], 6249], 'expected' => 20],
            ['args' => [[5, 10], 3], 'expected' => -1],
        ],
        'explanation' => [
            'en' => 'I use dynamic programming. best[x] is the fewest coins that make the amount x, with best[0] equal to zero. For every amount from one up to the target, I try each coin and take one plus the best answer for the amount minus that coin. If the target stays unreachable, I return minus one. It is O(amount times the number of coins) time and O(amount) space.',
            'pt' => 'Uso programação dinâmica. best[x] é o menor número de moedas que formam o valor x, com best[0] igual a zero. Para cada valor de um até o alvo, testo cada moeda e pego um mais a melhor resposta para o valor menos aquela moeda. Se o alvo continua inalcançável, devolvo menos um. É O(valor vezes o número de moedas) em tempo e O(valor) em espaço.',
        ],
        'solution' => <<<'CODE'
            <?php

            function coinChange(array $coins, int $amount): int
            {
                $unreachable = $amount + 1;
                $best = array_fill(0, $amount + 1, $unreachable);
                $best[0] = 0;
                for ($x = 1; $x <= $amount; $x++) {
                    foreach ($coins as $coin) {
                        if ($coin <= $x && $best[$x - $coin] + 1 < $best[$x]) {
                            $best[$x] = $best[$x - $coin] + 1;
                        }
                    }
                }

                return $best[$amount] === $unreachable ? -1 : $best[$amount];
            }
            CODE,
    ],
];
