<?php

/*
| TypeScript challenges. The format is described in javascript.php.
*/

return [
    [
        'slug' => 'reverse-words',
        'title' => 'Reverse Words in a String',
        'difficulty' => 'easy',
        'summary' => 'Strings, split e join: o aquecimento de muitas entrevistas.',
        'signature' => ['name' => 'reverseWords', 'params' => ['s' => 'string'], 'returns' => 'string'],
        'statement' => [
            'Given a string `s`, {{reverse the order}} of the words. A word is a sequence of characters that are not spaces.',
            'The input may have {{leading}} or {{trailing}} spaces and more than one space between two words. Return a string with the words in reverse order, separated by a single space, with no extra spaces.',
        ],
        'examples' => [
            ['input' => 's = "the sky is blue"', 'output' => '"blue is sky the"'],
            ['input' => 's = "  hello world  "', 'output' => '"world hello"', 'explanation' => 'The result has no leading or trailing spaces.'],
        ],
        'constraints' => ['1 <= s.length <= 10^4', '`s` contains English letters, digits and spaces.', 'There is at least one word in `s`.'],
        'glossary' => [
            'reverse the order' => 'Inverter a ordem: o último vem primeiro.',
            'leading' => 'No começo (leading spaces = espaços no início).',
            'trailing' => 'No fim (trailing spaces = espaços no final).',
        ],
        'hints' => [
            'Split the string on spaces. What do the empty pieces mean?',
            'Filter out the empty strings, reverse the array and join it with one space.',
        ],
        'tests' => [
            ['args' => ['the sky is blue'], 'expected' => 'blue is sky the'],
            ['args' => ['  hello world  '], 'expected' => 'world hello'],
            ['args' => ['a good   example'], 'expected' => 'example good a'],
            ['args' => ['single'], 'expected' => 'single'],
            ['args' => ['   spaces   only   around   '], 'expected' => 'around only spaces'],
        ],
        'explanation' => [
            'en' => 'I split the string on whitespace, drop the empty pieces that come from repeated spaces, reverse the list of words and join it with a single space. Every step is linear, so it is O(n) in time and O(n) in space.',
            'pt' => 'Separo a string pelos espaços, descarto os pedaços vazios que vêm de espaços repetidos, inverto a lista de palavras e junto com um espaço só. Cada passo é linear, então é O(n) em tempo e O(n) em espaço.',
        ],
        'solution' => <<<'CODE'
            function reverseWords(s: string): string {
              return s
                .split(' ')
                .filter((word) => word !== '')
                .reverse()
                .join(' ');
            }
            CODE,
    ],
    [
        'slug' => 'valid-anagram',
        'title' => 'Valid Anagram',
        'difficulty' => 'easy',
        'summary' => 'Contagem de letras com um mapa, comparada em uma passada.',
        'signature' => ['name' => 'isAnagram', 'params' => ['s' => 'string', 't' => 'string'], 'returns' => 'bool'],
        'statement' => [
            'Given two strings `s` and `t`, return `true` if `t` is an {{anagram}} of `s`, and `false` otherwise.',
            'An anagram uses all the original letters {{exactly once}}, in any order.',
        ],
        'examples' => [
            ['input' => 's = "anagram", t = "nagaram"', 'output' => 'true'],
            ['input' => 's = "rat", t = "car"', 'output' => 'false'],
        ],
        'constraints' => ['1 <= s.length, t.length <= 5 * 10^4', '`s` and `t` consist of lowercase English letters.'],
        'glossary' => [
            'anagram' => 'Anagrama: palavra formada pelas mesmas letras em outra ordem ("amor" e "roma").',
            'exactly once' => 'Exatamente uma vez: nem a mais, nem a menos.',
        ],
        'hints' => [
            'If the lengths are different, can it be an anagram?',
            'Count each letter of `s`, then subtract the letters of `t`. What should every count be at the end?',
        ],
        'tests' => [
            ['args' => ['anagram', 'nagaram'], 'expected' => true],
            ['args' => ['rat', 'car'], 'expected' => false],
            ['args' => ['a', 'ab'], 'expected' => false],
            ['args' => ['listen', 'silent'], 'expected' => true],
            ['args' => ['aacc', 'ccac'], 'expected' => false],
        ],
        'explanation' => [
            'en' => 'If the lengths differ, it cannot be an anagram. Otherwise I count the letters of the first string in a map and decrease the counts with the second one. If a count would go below zero, the strings are different. It is O(n) in time, and the map holds at most 26 letters, so the space is constant.',
            'pt' => 'Se os tamanhos são diferentes, não pode ser anagrama. Senão, conto as letras da primeira string num mapa e diminuo as contagens com a segunda. Se alguma contagem ficaria abaixo de zero, as strings são diferentes. É O(n) em tempo, e o mapa guarda no máximo 26 letras, então o espaço é constante.',
        ],
        'solution' => <<<'CODE'
            function isAnagram(s: string, t: string): boolean {
              if (s.length !== t.length) return false;
              const count = new Map<string, number>();
              for (const c of s) count.set(c, (count.get(c) ?? 0) + 1);
              for (const c of t) {
                const left = count.get(c) ?? 0;
                if (left === 0) return false;
                count.set(c, left - 1);
              }
              return true;
            }
            CODE,
    ],
    [
        'slug' => 'best-time-to-buy-and-sell-stock',
        'title' => 'Best Time to Buy and Sell Stock',
        'difficulty' => 'easy',
        'summary' => 'Uma passada guardando o menor preço: raciocínio guloso.',
        'signature' => ['name' => 'maxProfit', 'params' => ['prices' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'You are given an array `prices` where `prices[i]` is the price of a stock on day `i`.',
            'You want to {{maximize}} your {{profit}} by choosing one day to buy one stock and a different day {{in the future}} to sell it. Return the maximum profit you can get. If you cannot make any profit, return `0`.',
        ],
        'examples' => [
            ['input' => 'prices = [7, 1, 5, 3, 6, 4]', 'output' => '5', 'explanation' => 'Buy on day 1 (price 1) and sell on day 4 (price 6): 6 - 1 = 5.'],
            ['input' => 'prices = [7, 6, 4, 3, 1]', 'output' => '0', 'explanation' => 'The price only goes down, so no transaction makes a profit.'],
        ],
        'constraints' => ['1 <= prices.length <= 10^5', '0 <= prices[i] <= 10^4'],
        'glossary' => [
            'maximize' => 'Maximizar: deixar o maior possível.',
            'profit' => 'Lucro: preço de venda menos preço de compra.',
            'in the future' => 'No futuro: você só pode vender depois de comprar.',
        ],
        'hints' => [
            'Checking every pair of days works, but it is O(n²). Can you do it in one pass?',
            'Keep the lowest price seen so far. On each day, what profit would you make selling today?',
        ],
        'tests' => [
            ['args' => [[7, 1, 5, 3, 6, 4]], 'expected' => 5],
            ['args' => [[7, 6, 4, 3, 1]], 'expected' => 0],
            ['args' => [[1, 2]], 'expected' => 1],
            ['args' => [[5]], 'expected' => 0],
            ['args' => [[2, 4, 1, 7]], 'expected' => 6],
            ['args' => [[3, 3, 3]], 'expected' => 0],
        ],
        'explanation' => [
            'en' => 'I go through the prices once and keep the lowest price so far. For each day, the best sale is today\'s price minus that minimum, so I update the best profit. It runs in O(n) time with O(1) extra space.',
            'pt' => 'Percorro os preços uma vez e guardo o menor preço até agora. Para cada dia, a melhor venda é o preço de hoje menos esse mínimo, então atualizo o melhor lucro. Roda em O(n) de tempo com O(1) de espaço extra.',
        ],
        'solution' => <<<'CODE'
            function maxProfit(prices: number[]): number {
              let lowest = Infinity;
              let best = 0;
              for (const price of prices) {
                lowest = Math.min(lowest, price);
                best = Math.max(best, price - lowest);
              }
              return best;
            }
            CODE,
    ],
    [
        'slug' => 'move-zeroes',
        'title' => 'Move Zeroes',
        'difficulty' => 'easy',
        'summary' => 'Dois ponteiros, mantendo a ordem dos elementos.',
        'signature' => ['name' => 'moveZeroes', 'params' => ['nums' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'Given an integer array `nums`, move all the `0`s to the end of it while keeping the {{relative order}} of the {{non-zero}} elements.',
            'Try to do it {{in place}}, without making a copy of the array, and return the array.',
        ],
        'examples' => [
            ['input' => 'nums = [0, 1, 0, 3, 12]', 'output' => '[1, 3, 12, 0, 0]'],
            ['input' => 'nums = [0]', 'output' => '[0]'],
        ],
        'constraints' => ['1 <= nums.length <= 10^4', '-2^31 <= nums[i] <= 2^31 - 1'],
        'glossary' => [
            'relative order' => 'Ordem relativa: quem vinha antes continua vindo antes.',
            'non-zero' => 'Diferente de zero.',
            'in place' => 'No próprio array, sem criar outro (espaço extra O(1)).',
        ],
        'hints' => [
            'Keep a pointer to where the next non-zero number should go.',
            'After placing every non-zero number, what goes in the remaining positions?',
        ],
        'tests' => [
            ['args' => [[0, 1, 0, 3, 12]], 'expected' => [1, 3, 12, 0, 0]],
            ['args' => [[0]], 'expected' => [0]],
            ['args' => [[1, 2, 3]], 'expected' => [1, 2, 3]],
            ['args' => [[0, 0, 1]], 'expected' => [1, 0, 0]],
            ['args' => [[4, 0, -2, 0, 0, 7]], 'expected' => [4, -2, 7, 0, 0, 0]],
        ],
        'explanation' => [
            'en' => 'I use two pointers. One walks through the array, and the other marks where the next non-zero number goes. Each non-zero number is written at that position, and then I fill the rest with zeros. It is O(n) in time and O(1) in extra space, and the order of the non-zero numbers stays the same.',
            'pt' => 'Uso dois ponteiros. Um percorre o array e o outro marca onde vai o próximo número diferente de zero. Cada número diferente de zero é escrito nessa posição e, no fim, preencho o resto com zeros. É O(n) em tempo e O(1) de espaço extra, e a ordem dos números diferentes de zero continua a mesma.',
        ],
        'solution' => <<<'CODE'
            function moveZeroes(nums: number[]): number[] {
              let next = 0;
              for (const num of nums) {
                if (num !== 0) nums[next++] = num;
              }
              while (next < nums.length) nums[next++] = 0;
              return nums;
            }
            CODE,
    ],
    [
        'slug' => 'longest-common-prefix',
        'title' => 'Longest Common Prefix',
        'difficulty' => 'easy',
        'summary' => 'Comparação de strings, coluna por coluna.',
        'signature' => ['name' => 'longestCommonPrefix', 'params' => ['strs' => 'string[]'], 'returns' => 'string'],
        'statement' => [
            'Write a function to find the longest common {{prefix}} {{among}} an array of strings `strs`.',
            'If there is no common prefix, return an empty string `""`.',
        ],
        'examples' => [
            ['input' => 'strs = ["flower", "flow", "flight"]', 'output' => '"fl"'],
            ['input' => 'strs = ["dog", "racecar", "car"]', 'output' => '""', 'explanation' => 'There is no common prefix among the input strings.'],
        ],
        'constraints' => ['1 <= strs.length <= 200', '0 <= strs[i].length <= 200', '`strs[i]` consists of lowercase English letters.'],
        'glossary' => [
            'prefix' => 'Prefixo: o começo de uma palavra ("fl" é prefixo de "flower").',
            'among' => 'Entre (vários): comum a todos os itens.',
        ],
        'hints' => [
            'The answer cannot be longer than the shortest string.',
            'Compare the first string with every other string, one character position at a time.',
        ],
        'tests' => [
            ['args' => [['flower', 'flow', 'flight']], 'expected' => 'fl'],
            ['args' => [['dog', 'racecar', 'car']], 'expected' => ''],
            ['args' => [['alone']], 'expected' => 'alone'],
            ['args' => [['interview', 'internet', 'interval']], 'expected' => 'inter'],
            ['args' => [['', 'abc']], 'expected' => ''],
            ['args' => [['same', 'same']], 'expected' => 'same'],
        ],
        'explanation' => [
            'en' => 'I take the first string as a reference and check each position. For position i, every other string must have the same character there; at the first difference, or when a string ends, the prefix stops. In the worst case I read every character once, so it is O(S) time, where S is the total number of characters, with O(1) extra space.',
            'pt' => 'Uso a primeira string como referência e confiro cada posição. Na posição i, todas as outras strings precisam ter o mesmo caractere; na primeira diferença, ou quando uma string acaba, o prefixo para. No pior caso leio cada caractere uma vez, então é O(S) em tempo, sendo S o total de caracteres, com O(1) de espaço extra.',
        ],
        'solution' => <<<'CODE'
            function longestCommonPrefix(strs: string[]): string {
              const first = strs[0];
              for (let i = 0; i < first.length; i++) {
                for (const other of strs) {
                  if (i >= other.length || other[i] !== first[i]) return first.slice(0, i);
                }
              }
              return first;
            }
            CODE,
    ],
    [
        'slug' => 'product-of-array-except-self',
        'title' => 'Product of Array Except Self',
        'difficulty' => 'medium',
        'summary' => 'Prefixos e sufixos: resolver sem usar divisão.',
        'signature' => ['name' => 'productExceptSelf', 'params' => ['nums' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'Given an integer array `nums`, return an array `answer` such that `answer[i]` is equal to the {{product}} of all the elements of `nums` {{except}} `nums[i]`.',
            'You must write an algorithm that runs in O(n) time and {{without using}} the division operation.',
        ],
        'examples' => [
            ['input' => 'nums = [1, 2, 3, 4]', 'output' => '[24, 12, 8, 6]', 'explanation' => 'answer[0] = 2 * 3 * 4 = 24, answer[1] = 1 * 3 * 4 = 12, and so on.'],
            ['input' => 'nums = [-1, 1, 0, -3, 3]', 'output' => '[0, 0, 9, 0, 0]'],
        ],
        'constraints' => ['2 <= nums.length <= 10^5', '-30 <= nums[i] <= 30', 'Every product fits in a 32-bit integer.'],
        'glossary' => [
            'product' => 'Produto: o resultado de uma multiplicação.',
            'except' => 'Exceto, menos.',
            'without using' => 'Sem usar.',
        ],
        'hints' => [
            'For each position, the answer is (product of everything on the left) times (product of everything on the right).',
            'Fill the answer with the left products in one pass, then multiply by the right products in a second pass from the end.',
        ],
        'tests' => [
            ['args' => [[1, 2, 3, 4]], 'expected' => [24, 12, 8, 6]],
            ['args' => [[-1, 1, 0, -3, 3]], 'expected' => [0, 0, 9, 0, 0]],
            ['args' => [[2, 3]], 'expected' => [3, 2]],
            ['args' => [[0, 0, 5]], 'expected' => [0, 0, 0]],
            ['args' => [[5, -2, 1, 3]], 'expected' => [-6, 15, -30, -10]],
        ],
        'explanation' => [
            'en' => 'Each answer is the product of everything to the left times the product of everything to the right. In a first pass I write the left products into the output. In a second pass, from the end, I keep a running product of the right side and multiply it in. That is O(n) time, no division, and O(1) extra space besides the output.',
            'pt' => 'Cada resposta é o produto de tudo à esquerda vezes o produto de tudo à direita. Numa primeira passada escrevo os produtos da esquerda na saída. Numa segunda, do fim para o começo, mantenho o produto acumulado da direita e multiplico. Isso é O(n) em tempo, sem divisão, e O(1) de espaço extra além da saída.',
        ],
        'solution' => <<<'CODE'
            function productExceptSelf(nums: number[]): number[] {
              const answer: number[] = new Array(nums.length).fill(1);
              let left = 1;
              for (let i = 0; i < nums.length; i++) {
                answer[i] = left;
                left *= nums[i];
              }
              let right = 1;
              for (let i = nums.length - 1; i >= 0; i--) {
                answer[i] *= right;
                right *= nums[i];
              }
              return answer;
            }
            CODE,
    ],
];
