<?php

/*
| Java challenges. The format is described in javascript.php. The user
| writes the method inside a Solution class.
*/

return [
    [
        'slug' => 'binary-search',
        'title' => 'Binary Search',
        'difficulty' => 'easy',
        'summary' => 'Busca binária: dividir o problema pela metade a cada passo.',
        'signature' => ['name' => 'search', 'params' => ['nums' => 'int[]', 'target' => 'int'], 'returns' => 'int'],
        'statement' => [
            'Given an array of integers `nums` sorted in {{ascending order}} and an integer `target`, write a method to search for `target` in `nums`. If `target` exists, return its index; otherwise, return `-1`.',
            'Your algorithm must run in O(log n) time, so you cannot check every element.',
        ],
        'examples' => [
            ['input' => 'nums = [-1, 0, 3, 5, 9, 12], target = 9', 'output' => '4', 'explanation' => '9 is at index 4.'],
            ['input' => 'nums = [-1, 0, 3, 5, 9, 12], target = 2', 'output' => '-1'],
        ],
        'constraints' => ['1 <= nums.length <= 10^4', 'All the integers in `nums` are unique.', '`nums` is sorted in ascending order.'],
        'glossary' => [
            'ascending order' => 'Ordem crescente, do menor para o maior.',
        ],
        'hints' => [
            'Compare `target` with the middle element. Which half can you discard?',
            'Keep two indices, low and high, and stop when low passes high. Watch out for overflow when you compute the middle.',
        ],
        'tests' => [
            ['args' => [[-1, 0, 3, 5, 9, 12], 9], 'expected' => 4],
            ['args' => [[-1, 0, 3, 5, 9, 12], 2], 'expected' => -1],
            ['args' => [[5], 5], 'expected' => 0],
            ['args' => [[5], -5], 'expected' => -1],
            ['args' => [[1, 3, 5, 7, 9, 11, 13], 1], 'expected' => 0],
            ['args' => [[1, 3, 5, 7, 9, 11, 13], 13], 'expected' => 6],
        ],
        'explanation' => [
            'en' => 'I keep a low and a high index. At each step I look at the middle element: if it is the target, I return its index; if it is smaller, the target can only be on the right half; otherwise, on the left half. The search space is cut in half every time, so it is O(log n) time and O(1) space.',
            'pt' => 'Mantenho um índice baixo e um alto. A cada passo olho o elemento do meio: se é o alvo, devolvo o índice; se é menor, o alvo só pode estar na metade da direita; senão, na da esquerda. O espaço de busca cai pela metade a cada vez, então é O(log n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            class Solution {
                public int search(int[] nums, int target) {
                    int low = 0;
                    int high = nums.length - 1;
                    while (low <= high) {
                        int middle = low + (high - low) / 2;
                        if (nums[middle] == target) {
                            return middle;
                        }
                        if (nums[middle] < target) {
                            low = middle + 1;
                        } else {
                            high = middle - 1;
                        }
                    }
                    return -1;
                }
            }
            CODE,
    ],
    [
        'slug' => 'climbing-stairs',
        'title' => 'Climbing Stairs',
        'difficulty' => 'easy',
        'summary' => 'A porta de entrada da programação dinâmica.',
        'signature' => ['name' => 'climbStairs', 'params' => ['n' => 'int'], 'returns' => 'int'],
        'statement' => [
            'You are climbing a {{staircase}}. It takes `n` steps to reach the top.',
            'Each time you can climb either 1 or 2 steps. In how many {{distinct ways}} can you climb to the top?',
        ],
        'examples' => [
            ['input' => 'n = 2', 'output' => '2', 'explanation' => '1 step + 1 step, or 2 steps.'],
            ['input' => 'n = 3', 'output' => '3', 'explanation' => '1 + 1 + 1, 1 + 2, or 2 + 1.'],
        ],
        'constraints' => ['1 <= n <= 45'],
        'glossary' => [
            'staircase' => 'Escada.',
            'distinct ways' => 'Maneiras diferentes.',
        ],
        'hints' => [
            'To reach step n, your last move came from step n - 1 or from step n - 2.',
            'So ways(n) = ways(n - 1) + ways(n - 2). Do you need to keep the whole table, or just the last two values?',
        ],
        'tests' => [
            ['args' => [1], 'expected' => 1],
            ['args' => [2], 'expected' => 2],
            ['args' => [3], 'expected' => 3],
            ['args' => [5], 'expected' => 8],
            ['args' => [10], 'expected' => 89],
            ['args' => [45], 'expected' => 1836311903],
        ],
        'explanation' => [
            'en' => 'The last move to step n comes from step n minus one or n minus two, so the number of ways is the sum of the ways to reach those two steps. That is the Fibonacci sequence. I compute it bottom-up keeping only the last two values, which is O(n) time and O(1) space, and avoids the exponential cost of plain recursion.',
            'pt' => 'O último movimento até o degrau n vem do degrau n menos um ou n menos dois, então o número de maneiras é a soma das maneiras de chegar a esses dois. É a sequência de Fibonacci. Calculo de baixo para cima guardando só os dois últimos valores, o que é O(n) em tempo e O(1) em espaço, e evita o custo exponencial da recursão simples.',
        ],
        'solution' => <<<'CODE'
            class Solution {
                public int climbStairs(int n) {
                    int previous = 1;
                    int current = 1;
                    for (int step = 2; step <= n; step++) {
                        int next = previous + current;
                        previous = current;
                        current = next;
                    }
                    return current;
                }
            }
            CODE,
    ],
    [
        'slug' => 'majority-element',
        'title' => 'Majority Element',
        'difficulty' => 'easy',
        'summary' => 'Contagem com HashMap ou o voto de Boyer-Moore.',
        'signature' => ['name' => 'majorityElement', 'params' => ['nums' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'Given an array `nums` of size `n`, return the {{majority element}}.',
            'The majority element is the element that appears {{more than}} n / 2 times. You may assume that the majority element always exists in the array.',
        ],
        'examples' => [
            ['input' => 'nums = [3, 2, 3]', 'output' => '3'],
            ['input' => 'nums = [2, 2, 1, 1, 1, 2, 2]', 'output' => '2'],
        ],
        'constraints' => ['1 <= nums.length <= 5 * 10^4', '-10^9 <= nums[i] <= 10^9'],
        'glossary' => [
            'majority element' => 'Elemento majoritário: o que aparece na maioria das posições.',
            'more than' => 'Mais que (estritamente): mais da metade, não exatamente a metade.',
        ],
        'hints' => [
            'A HashMap with the count of each number solves it in O(n) time and O(n) space.',
            'For O(1) space: keep a candidate and a counter. Equal numbers add one, different numbers remove one. What happens when the counter reaches zero?',
        ],
        'tests' => [
            ['args' => [[3, 2, 3]], 'expected' => 3],
            ['args' => [[2, 2, 1, 1, 1, 2, 2]], 'expected' => 2],
            ['args' => [[7]], 'expected' => 7],
            ['args' => [[1, 5, 5, 1, 5]], 'expected' => 5],
            ['args' => [[-4, -4, 9]], 'expected' => -4],
        ],
        'explanation' => [
            'en' => 'I use the Boyer-Moore voting algorithm. I keep a candidate and a counter: the same number increases the counter, a different number decreases it, and when the counter is zero the current number becomes the new candidate. Because the majority appears more than half the time, it is the candidate at the end. O(n) time and O(1) space.',
            'pt' => 'Uso o algoritmo de votação de Boyer-Moore. Mantenho um candidato e um contador: o mesmo número aumenta o contador, um número diferente diminui, e quando o contador está em zero o número atual vira o novo candidato. Como o majoritário aparece em mais da metade das posições, ele é o candidato no fim. O(n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            class Solution {
                public int majorityElement(int[] nums) {
                    int candidate = nums[0];
                    int count = 0;
                    for (int num : nums) {
                        if (count == 0) {
                            candidate = num;
                        }
                        count += num == candidate ? 1 : -1;
                    }
                    return candidate;
                }
            }
            CODE,
    ],
    [
        'slug' => 'plus-one',
        'title' => 'Plus One',
        'difficulty' => 'easy',
        'summary' => 'Arrays e o "vai um" da soma, incluindo o caso 999.',
        'signature' => ['name' => 'plusOne', 'params' => ['digits' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'You are given a large integer represented as an array `digits`, where each `digits[i]` is the i-th {{digit}} of the integer, from the most {{significant}} to the least significant. The integer has no leading zeros.',
            '{{Increment}} the large integer by one and return the resulting array of digits.',
        ],
        'examples' => [
            ['input' => 'digits = [1, 2, 3]', 'output' => '[1, 2, 4]', 'explanation' => '123 + 1 = 124.'],
            ['input' => 'digits = [9, 9]', 'output' => '[1, 0, 0]', 'explanation' => '99 + 1 = 100: the result has one more digit.'],
        ],
        'constraints' => ['1 <= digits.length <= 100', '0 <= digits[i] <= 9'],
        'glossary' => [
            'digit' => 'Dígito, algarismo de 0 a 9.',
            'significant' => 'Significativo: o mais significativo é o da esquerda (vale mais).',
            'Increment' => 'Incrementar, somar um.',
        ],
        'hints' => [
            'Start from the last digit. When does the carry stop?',
            'If every digit was 9, you need a new array that is one position longer.',
        ],
        'tests' => [
            ['args' => [[1, 2, 3]], 'expected' => [1, 2, 4]],
            ['args' => [[4, 3, 2, 1]], 'expected' => [4, 3, 2, 2]],
            ['args' => [[9]], 'expected' => [1, 0]],
            ['args' => [[9, 9, 9]], 'expected' => [1, 0, 0, 0]],
            ['args' => [[1, 9, 9]], 'expected' => [2, 0, 0]],
            ['args' => [[0]], 'expected' => [1]],
        ],
        'explanation' => [
            'en' => 'I walk from the last digit to the first. If a digit is less than nine, I add one and return, because there is no carry. A nine becomes zero and the carry moves left. If I get past the first digit, every digit was nine, so I return a new array with a one followed by zeros. O(n) time, and O(1) extra space except in the all-nines case.',
            'pt' => 'Percorro do último dígito ao primeiro. Se um dígito é menor que nove, somo um e devolvo, porque não há "vai um". Um nove vira zero e o "vai um" segue para a esquerda. Se passo do primeiro dígito, todos eram nove, então devolvo um array novo com um seguido de zeros. O(n) em tempo e O(1) de espaço extra, exceto no caso de só noves.',
        ],
        'solution' => <<<'CODE'
            class Solution {
                public int[] plusOne(int[] digits) {
                    for (int i = digits.length - 1; i >= 0; i--) {
                        if (digits[i] < 9) {
                            digits[i]++;
                            return digits;
                        }
                        digits[i] = 0;
                    }
                    int[] result = new int[digits.length + 1];
                    result[0] = 1;
                    return result;
                }
            }
            CODE,
    ],
    [
        'slug' => 'rotate-array',
        'title' => 'Rotate Array',
        'difficulty' => 'medium',
        'summary' => 'Rotação com o truque de inverter três vezes.',
        'signature' => ['name' => 'rotate', 'params' => ['nums' => 'int[]', 'k' => 'int'], 'returns' => 'int[]'],
        'statement' => [
            'Given an integer array `nums`, {{rotate}} the array to the right by `k` steps, where `k` is {{non-negative}}, and return it.',
            'Rotating to the right by one step moves the last element to the front. `k` may be larger than the length of the array.',
        ],
        'examples' => [
            ['input' => 'nums = [1, 2, 3, 4, 5, 6, 7], k = 3', 'output' => '[5, 6, 7, 1, 2, 3, 4]', 'explanation' => 'One step: [7, 1, 2, 3, 4, 5, 6]. Two: [6, 7, 1, 2, 3, 4, 5]. Three: [5, 6, 7, 1, 2, 3, 4].'],
            ['input' => 'nums = [-1, -100, 3, 99], k = 2', 'output' => '[3, 99, -1, -100]'],
        ],
        'constraints' => ['1 <= nums.length <= 10^5', '0 <= k <= 10^5'],
        'glossary' => [
            'rotate' => 'Rotacionar, girar: os elementos que saem de um lado entram pelo outro.',
            'non-negative' => 'Não negativo: zero ou maior.',
        ],
        'hints' => [
            'Rotating by the array length gives the same array. What does `k % n` tell you?',
            'Try it: reverse the whole array, then reverse the first k elements, then reverse the rest.',
        ],
        'tests' => [
            ['args' => [[1, 2, 3, 4, 5, 6, 7], 3], 'expected' => [5, 6, 7, 1, 2, 3, 4]],
            ['args' => [[-1, -100, 3, 99], 2], 'expected' => [3, 99, -1, -100]],
            ['args' => [[1, 2], 0], 'expected' => [1, 2]],
            ['args' => [[1, 2, 3], 4], 'expected' => [3, 1, 2]],
            ['args' => [[9], 7], 'expected' => [9]],
        ],
        'explanation' => [
            'en' => 'First I take k modulo the length, since a full rotation changes nothing. Then I reverse the whole array, reverse the first k elements and reverse the remaining ones. Each element moves to its rotated position in place. It is O(n) time and O(1) extra space.',
            'pt' => 'Primeiro faço k módulo o tamanho, porque uma rotação completa não muda nada. Depois inverto o array inteiro, inverto os k primeiros elementos e inverto o restante. Cada elemento vai para a posição rotacionada no próprio array. É O(n) em tempo e O(1) de espaço extra.',
        ],
        'solution' => <<<'CODE'
            class Solution {
                public int[] rotate(int[] nums, int k) {
                    int n = nums.length;
                    k %= n;
                    reverse(nums, 0, n - 1);
                    reverse(nums, 0, k - 1);
                    reverse(nums, k, n - 1);
                    return nums;
                }

                private void reverse(int[] nums, int from, int to) {
                    while (from < to) {
                        int temp = nums[from];
                        nums[from++] = nums[to];
                        nums[to--] = temp;
                    }
                }
            }
            CODE,
    ],
    [
        'slug' => 'container-with-most-water',
        'title' => 'Container With Most Water',
        'difficulty' => 'medium',
        'summary' => 'Dois ponteiros nas pontas e o argumento de por que funciona.',
        'signature' => ['name' => 'maxArea', 'params' => ['height' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'You are given an integer array `height` of length `n`. There are `n` vertical lines, where line `i` goes from `(i, 0)` to `(i, height[i])`.',
            'Find two lines that, together with the x-axis, form a {{container}} that holds the most water, and return the maximum amount of water it can {{store}}. The water level is limited by the {{shorter}} line.',
        ],
        'examples' => [
            ['input' => 'height = [1, 8, 6, 2, 5, 4, 8, 3, 7]', 'output' => '49', 'explanation' => 'The lines at index 1 (8) and index 8 (7) hold min(8, 7) * (8 - 1) = 49.'],
            ['input' => 'height = [1, 1]', 'output' => '1'],
        ],
        'constraints' => ['2 <= n <= 10^5', '0 <= height[i] <= 10^4'],
        'glossary' => [
            'container' => 'Recipiente, o "pote" formado pelas duas linhas.',
            'store' => 'Armazenar, guardar.',
            'shorter' => 'Mais baixa (comparativo de "short").',
        ],
        'hints' => [
            'Start with the widest container: one pointer at each end.',
            'Moving the taller line inward can never help, because the shorter one still limits the water. Which pointer should move?',
        ],
        'tests' => [
            ['args' => [[1, 8, 6, 2, 5, 4, 8, 3, 7]], 'expected' => 49],
            ['args' => [[1, 1]], 'expected' => 1],
            ['args' => [[4, 3, 2, 1, 4]], 'expected' => 16],
            ['args' => [[1, 2, 1]], 'expected' => 2],
            ['args' => [[0, 0, 0]], 'expected' => 0],
        ],
        'explanation' => [
            'en' => 'I start with one pointer at each end, which is the widest container. The area is the shorter height times the distance. Then I move the pointer at the shorter line inward, because moving the taller one can only make the width smaller without raising the limit. I keep the best area. One pass, O(n) time and O(1) space.',
            'pt' => 'Começo com um ponteiro em cada ponta, que é o recipiente mais largo. A área é a altura menor vezes a distância. Depois movo para dentro o ponteiro da linha mais baixa, porque mover a mais alta só diminui a largura sem subir o limite. Guardo a melhor área. Uma passada, O(n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            class Solution {
                public int maxArea(int[] height) {
                    int left = 0;
                    int right = height.length - 1;
                    int best = 0;
                    while (left < right) {
                        int area = Math.min(height[left], height[right]) * (right - left);
                        best = Math.max(best, area);
                        if (height[left] < height[right]) {
                            left++;
                        } else {
                            right--;
                        }
                    }
                    return best;
                }
            }
            CODE,
    ],
];
