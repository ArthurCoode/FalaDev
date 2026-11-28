<?php

/*
| Python challenges. The format is described in javascript.php. Function
| names become snake_case (containsDuplicate -> contains_duplicate).
*/

return [
    [
        'slug' => 'contains-duplicate',
        'title' => 'Contains Duplicate',
        'difficulty' => 'easy',
        'summary' => 'Conjuntos (set) para saber o que já apareceu.',
        'signature' => ['name' => 'containsDuplicate', 'params' => ['nums' => 'int[]'], 'returns' => 'bool'],
        'statement' => [
            'Given an integer list `nums`, return `True` if any value {{appears at least twice}} in the list, and return `False` if every element is {{distinct}}.',
        ],
        'examples' => [
            ['input' => 'nums = [1, 2, 3, 1]', 'output' => 'True', 'explanation' => 'The value 1 appears at index 0 and at index 3.'],
            ['input' => 'nums = [1, 2, 3, 4]', 'output' => 'False'],
        ],
        'constraints' => ['1 <= len(nums) <= 10^5', '-10^9 <= nums[i] <= 10^9'],
        'glossary' => [
            'appears at least twice' => 'Aparece pelo menos duas vezes.',
            'distinct' => 'Distinto, diferente de todos os outros.',
        ],
        'hints' => [
            'Sorting puts equal values next to each other. Is there something faster than O(n log n)?',
            'A set remembers what you have seen. What do you do when a number is already in it?',
        ],
        'tests' => [
            ['args' => [[1, 2, 3, 1]], 'expected' => true],
            ['args' => [[1, 2, 3, 4]], 'expected' => false],
            ['args' => [[1, 1, 1, 3, 3, 4, 3, 2, 4, 2]], 'expected' => true],
            ['args' => [[7]], 'expected' => false],
            ['args' => [[-1, 0, -1]], 'expected' => true],
        ],
        'explanation' => [
            'en' => 'I walk through the list with a set of the values I have already seen. If the current value is in the set, there is a duplicate and I return True right away. If the loop ends, every value is distinct. That is O(n) time and O(n) space.',
            'pt' => 'Percorro a lista com um set dos valores que já vi. Se o valor atual está no set, há um repetido e devolvo True na hora. Se o laço termina, todos os valores são distintos. Isso é O(n) em tempo e O(n) em espaço.',
        ],
        'solution' => <<<'CODE'
            def contains_duplicate(nums: list[int]) -> bool:
                seen = set()
                for num in nums:
                    if num in seen:
                        return True
                    seen.add(num)
                return False
            CODE,
    ],
    [
        'slug' => 'roman-to-integer',
        'title' => 'Roman to Integer',
        'difficulty' => 'easy',
        'summary' => 'Dicionários e a regra da subtração dos algarismos romanos.',
        'signature' => ['name' => 'romanToInt', 'params' => ['s' => 'string'], 'returns' => 'int'],
        'statement' => [
            'Roman {{numerals}} use seven symbols: I = 1, V = 5, X = 10, L = 50, C = 100, D = 500 and M = 1000. Usually they are written from largest to smallest, left to right, and their values are added: XII = 12.',
            'When a smaller symbol comes {{right before}} a larger one, it is {{subtracted}} instead: IV = 4, IX = 9, XL = 40, XC = 90, CD = 400 and CM = 900. Given a Roman numeral `s`, convert it to an integer.',
        ],
        'examples' => [
            ['input' => 's = "LVIII"', 'output' => '58', 'explanation' => 'L = 50, V = 5, III = 3.'],
            ['input' => 's = "MCMXCIV"', 'output' => '1994', 'explanation' => 'M = 1000, CM = 900, XC = 90, IV = 4.'],
        ],
        'constraints' => ['1 <= len(s) <= 15', '`s` is a valid Roman numeral between 1 and 3999.'],
        'glossary' => [
            'numerals' => 'Algarismos (Roman numerals = algarismos romanos).',
            'right before' => 'Logo antes, imediatamente antes.',
            'subtracted' => 'Subtraído: o valor é tirado, não somado.',
        ],
        'hints' => [
            'Map each symbol to its value with a dictionary.',
            'Look at the next symbol: if it is larger than the current one, subtract the current value; otherwise, add it.',
        ],
        'tests' => [
            ['args' => ['III'], 'expected' => 3],
            ['args' => ['LVIII'], 'expected' => 58],
            ['args' => ['MCMXCIV'], 'expected' => 1994],
            ['args' => ['IV'], 'expected' => 4],
            ['args' => ['XLII'], 'expected' => 42],
            ['args' => ['MMMCMXCIX'], 'expected' => 3999],
        ],
        'explanation' => [
            'en' => 'I keep the value of each symbol in a dictionary and read the string from left to right. If the next symbol is larger, the current one is subtracted, like the I in IV; otherwise it is added. It is O(n) in time and O(1) in space, because the dictionary has only seven entries.',
            'pt' => 'Guardo o valor de cada símbolo num dicionário e leio a string da esquerda para a direita. Se o próximo símbolo é maior, o atual é subtraído, como o I de IV; senão, é somado. É O(n) em tempo e O(1) em espaço, porque o dicionário tem só sete entradas.',
        ],
        'solution' => <<<'CODE'
            def roman_to_int(s: str) -> int:
                values = {"I": 1, "V": 5, "X": 10, "L": 50, "C": 100, "D": 500, "M": 1000}
                total = 0
                for i, symbol in enumerate(s):
                    value = values[symbol]
                    if i + 1 < len(s) and values[s[i + 1]] > value:
                        total -= value
                    else:
                        total += value
                return total
            CODE,
    ],
    [
        'slug' => 'missing-number',
        'title' => 'Missing Number',
        'difficulty' => 'easy',
        'summary' => 'Um truque de matemática: a soma de 0 a n.',
        'signature' => ['name' => 'missingNumber', 'params' => ['nums' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'Given a list `nums` containing `n` distinct numbers {{in the range}} `[0, n]`, return the only number in the range that is {{missing}} from the list.',
        ],
        'examples' => [
            ['input' => 'nums = [3, 0, 1]', 'output' => '2', 'explanation' => 'n = 3, so the range is [0, 3]. 2 is missing.'],
            ['input' => 'nums = [9, 6, 4, 2, 3, 5, 7, 0, 1]', 'output' => '8'],
        ],
        'constraints' => ['n == len(nums)', '1 <= n <= 10^4', 'All the numbers in `nums` are unique.'],
        'glossary' => [
            'in the range' => 'No intervalo: [0, n] vai de 0 até n, incluindo os dois.',
            'missing' => 'Faltando, ausente.',
        ],
        'hints' => [
            'What is the sum of all the numbers from 0 to n?',
            'Compare that sum with the sum of the list. Can you solve it in O(1) extra space?',
        ],
        'tests' => [
            ['args' => [[3, 0, 1]], 'expected' => 2],
            ['args' => [[0, 1]], 'expected' => 2],
            ['args' => [[9, 6, 4, 2, 3, 5, 7, 0, 1]], 'expected' => 8],
            ['args' => [[1]], 'expected' => 0],
            ['args' => [[0]], 'expected' => 1],
        ],
        'explanation' => [
            'en' => 'The numbers from 0 to n add up to n times n plus one, divided by two. I subtract the sum of the list from that total, and what is left is the missing number. It takes O(n) time to add the list and O(1) extra space.',
            'pt' => 'Os números de 0 a n somam n vezes n mais um, dividido por dois. Subtraio a soma da lista desse total, e o que sobra é o número que falta. Leva O(n) em tempo para somar a lista e O(1) de espaço extra.',
        ],
        'solution' => <<<'CODE'
            def missing_number(nums: list[int]) -> int:
                n = len(nums)
                return n * (n + 1) // 2 - sum(nums)
            CODE,
    ],
    [
        'slug' => 'maximum-subarray',
        'title' => 'Maximum Subarray',
        'difficulty' => 'medium',
        'summary' => 'Algoritmo de Kadane: programação dinâmica em uma passada.',
        'signature' => ['name' => 'maxSubArray', 'params' => ['nums' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'Given an integer list `nums`, find the {{subarray}} with the largest sum, and return its sum.',
            'A subarray is a {{contiguous}}, {{non-empty}} sequence of elements of the list.',
        ],
        'examples' => [
            ['input' => 'nums = [-2, 1, -3, 4, -1, 2, 1, -5, 4]', 'output' => '6', 'explanation' => 'The subarray [4, -1, 2, 1] has the largest sum, 6.'],
            ['input' => 'nums = [5, 4, -1, 7, 8]', 'output' => '23'],
        ],
        'constraints' => ['1 <= len(nums) <= 10^5', '-10^4 <= nums[i] <= 10^4'],
        'glossary' => [
            'subarray' => 'Subarray: um trecho seguido da lista.',
            'contiguous' => 'Contíguo: elementos vizinhos, sem pular nenhum.',
            'non-empty' => 'Não vazio: tem pelo menos um elemento.',
        ],
        'hints' => [
            'If the best sum ending at the previous position is negative, is it worth keeping?',
            'For each number, the best subarray ending here is either the number alone or the number plus the best one ending just before.',
        ],
        'tests' => [
            ['args' => [[-2, 1, -3, 4, -1, 2, 1, -5, 4]], 'expected' => 6],
            ['args' => [[1]], 'expected' => 1],
            ['args' => [[5, 4, -1, 7, 8]], 'expected' => 23],
            ['args' => [[-3, -1, -2]], 'expected' => -1],
            ['args' => [[2, -1, 2, -1, 2]], 'expected' => 4],
        ],
        'explanation' => [
            'en' => 'I use Kadane\'s algorithm. For each number, the best sum of a subarray that ends there is either the number alone or the number plus the best sum ending at the previous position. I keep the largest value I have seen. One pass, so O(n) time and O(1) space, and it also works when every number is negative.',
            'pt' => 'Uso o algoritmo de Kadane. Para cada número, a melhor soma de um subarray que termina nele é o número sozinho ou o número mais a melhor soma terminando na posição anterior. Guardo o maior valor visto. Uma passada só, então O(n) em tempo e O(1) em espaço, e funciona também quando todos os números são negativos.',
        ],
        'solution' => <<<'CODE'
            def max_sub_array(nums: list[int]) -> int:
                best = current = nums[0]
                for num in nums[1:]:
                    current = max(num, current + num)
                    best = max(best, current)
                return best
            CODE,
    ],
    [
        'slug' => 'top-k-frequent-elements',
        'title' => 'Top K Frequent Elements',
        'difficulty' => 'medium',
        'summary' => 'Contagem com Counter e ordenação por frequência.',
        'signature' => ['name' => 'topKFrequent', 'params' => ['nums' => 'int[]', 'k' => 'int'], 'returns' => 'int[]'],
        'statement' => [
            'Given an integer list `nums` and an integer `k`, return the `k` most {{frequent}} elements.',
            'Return them from the most frequent to the least frequent. If two elements appear the same number of times, the smaller one comes first. You may assume the answer is {{unique}} under this rule.',
        ],
        'examples' => [
            ['input' => 'nums = [1, 1, 1, 2, 2, 3], k = 2', 'output' => '[1, 2]', 'explanation' => '1 appears 3 times and 2 appears twice.'],
            ['input' => 'nums = [4, 4, 5, 5, 6], k = 2', 'output' => '[4, 5]', 'explanation' => '4 and 5 both appear twice; 4 is smaller, so it comes first.'],
        ],
        'constraints' => ['1 <= len(nums) <= 10^5', '1 <= k <= the number of distinct elements'],
        'glossary' => [
            'frequent' => 'Frequente: que aparece muitas vezes.',
            'unique' => 'Único: só existe uma resposta certa.',
        ],
        'hints' => [
            'First count how many times each number appears. `collections.Counter` does that for you.',
            'Sort the distinct numbers by a key: the count, highest first, then the number itself.',
        ],
        'tests' => [
            ['args' => [[1, 1, 1, 2, 2, 3], 2], 'expected' => [1, 2]],
            ['args' => [[1], 1], 'expected' => [1]],
            ['args' => [[4, 4, 5, 5, 6], 2], 'expected' => [4, 5]],
            ['args' => [[3, 3, 3, -1, -1, 2, 2, 2, 7], 3], 'expected' => [2, 3, -1]],
            ['args' => [[5, 6, 7], 3], 'expected' => [5, 6, 7]],
        ],
        'explanation' => [
            'en' => 'I count each number with a Counter. Then I sort the distinct numbers by count in descending order, breaking ties by the number itself, and take the first k. Counting is O(n) and sorting the distinct values is O(m log m), where m is how many distinct values there are.',
            'pt' => 'Conto cada número com um Counter. Depois ordeno os números distintos pela contagem, do maior para o menor, desempatando pelo próprio número, e pego os k primeiros. Contar é O(n) e ordenar os valores distintos é O(m log m), sendo m a quantidade de valores distintos.',
        ],
        'solution' => <<<'CODE'
            from collections import Counter


            def top_k_frequent(nums: list[int], k: int) -> list[int]:
                counts = Counter(nums)
                ordered = sorted(counts, key=lambda num: (-counts[num], num))
                return ordered[:k]
            CODE,
    ],
    [
        'slug' => 'longest-substring-without-repeating-characters',
        'title' => 'Longest Substring Without Repeating Characters',
        'difficulty' => 'medium',
        'summary' => 'Janela deslizante: um dos padrões mais cobrados.',
        'signature' => ['name' => 'lengthOfLongestSubstring', 'params' => ['s' => 'string'], 'returns' => 'int'],
        'statement' => [
            'Given a string `s`, find the length of the longest {{substring}} without repeating characters.',
        ],
        'examples' => [
            ['input' => 's = "abcabcbb"', 'output' => '3', 'explanation' => 'The answer is "abc", with length 3.'],
            ['input' => 's = "pwwkew"', 'output' => '3', 'explanation' => 'The answer is "wke". "pwke" is a subsequence, not a substring.'],
        ],
        'constraints' => ['0 <= len(s) <= 5 * 10^4', '`s` consists of English letters, digits, symbols and spaces.'],
        'glossary' => [
            'substring' => 'Substring: um trecho seguido da string.',
            'subsequence' => 'Subsequência: caracteres na ordem, mas podendo pular alguns.',
        ],
        'hints' => [
            'Keep a window [left, right] with no repeated characters and move right one step at a time.',
            'Remember the last index of each character. When you see a repeat inside the window, where should left jump to?',
        ],
        'tests' => [
            ['args' => ['abcabcbb'], 'expected' => 3],
            ['args' => ['bbbbb'], 'expected' => 1],
            ['args' => ['pwwkew'], 'expected' => 3],
            ['args' => [''], 'expected' => 0],
            ['args' => ['abba'], 'expected' => 2],
            ['args' => ['dvdf'], 'expected' => 3],
            ['args' => [' '], 'expected' => 1],
        ],
        'explanation' => [
            'en' => 'I use a sliding window and a dictionary with the last index of each character. When the character at the right end was already seen inside the window, I move the left end to just after that last position. The window never has a repeat, and I keep its largest length. Each character is visited once, so it is O(n) time.',
            'pt' => 'Uso uma janela deslizante e um dicionário com o último índice de cada caractere. Quando o caractere na ponta direita já apareceu dentro da janela, levo a ponta esquerda para logo depois dessa última posição. A janela nunca tem repetição, e guardo o maior tamanho dela. Cada caractere é visitado uma vez, então é O(n) em tempo.',
        ],
        'solution' => <<<'CODE'
            def length_of_longest_substring(s: str) -> int:
                last = {}
                left = best = 0
                for right, char in enumerate(s):
                    if char in last and last[char] >= left:
                        left = last[char] + 1
                    last[char] = right
                    best = max(best, right - left + 1)
                return best
            CODE,
    ],
];
