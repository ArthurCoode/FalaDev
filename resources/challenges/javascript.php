<?php

/*
|--------------------------------------------------------------------------
| Code challenges
|--------------------------------------------------------------------------
|
| One file per language (App\Enums\ChallengeLanguage), six problems each,
| with the statement in English. Read by App\Challenges\ChallengeCatalog and
| checked by tests/Unit/Challenges/ChallengeCatalogTest.php. Every file
| follows the format described here.
|
| - "slug" is stored with the user's solution: never rename it. Slugs are
|   unique across all languages.
| - "signature" is the function in camelCase with typed parameters (types in
|   App\Challenges\ValueType). Each language writes it in its own style
|   (two_sum in Python, TwoSum in C#) and builds the starter code from it.
| - The statement marks glossary terms as {{term}}; every marked term needs
|   an entry in "glossary" (the Portuguese explanation shown on hover).
| - "tests" run on the server against the user's function, in order. Each
|   has "args" (the arguments, as a list) and "expected" (compared by value).
| - "explanation" is a model answer for "explain your solution", in English
|   with the Portuguese translation, shown once the tests pass.
| - "solution" is a reference solution, never sent to the page. It must pass
|   every test: "php artisan challenges:verify" runs it.
|
*/

return [
    [
        'slug' => 'fizz-buzz',
        'title' => 'Fizz Buzz',
        'difficulty' => 'easy',
        'summary' => 'O aquecimento clássico: múltiplos, divisibilidade e listas.',
        'signature' => ['name' => 'fizzBuzz', 'params' => ['n' => 'int'], 'returns' => 'string[]'],
        'statement' => [
            'Given an integer `n`, {{return}} an array of strings for the numbers from 1 to `n`, {{inclusive}}.',
            'For each number: if it is {{divisible by}} 3 and 5, use "FizzBuzz"; if it is only divisible by 3, use "Fizz"; if only by 5, use "Buzz". {{Otherwise}}, use the number itself as a string.',
        ],
        'examples' => [
            ['input' => 'n = 5', 'output' => '["1", "2", "Fizz", "4", "Buzz"]'],
        ],
        'constraints' => ['1 <= n <= 10^4'],
        'glossary' => [
            'return' => 'Devolver (o retorno da função). "Return an array" = a função devolve uma lista.',
            'inclusive' => 'Incluindo os extremos: de 1 a n, com o próprio n.',
            'divisible by' => 'Divisível por: a divisão não deixa resto (n % 3 === 0).',
            'Otherwise' => 'Caso contrário, senão.',
        ],
        'hints' => [
            'Check the "divisible by 3 and 5" case first. Why does the order matter?',
            'The remainder operator `%` tells you if a number is divisible by another.',
        ],
        'tests' => [
            ['args' => [1], 'expected' => ['1']],
            ['args' => [3], 'expected' => ['1', '2', 'Fizz']],
            ['args' => [5], 'expected' => ['1', '2', 'Fizz', '4', 'Buzz']],
            ['args' => [15], 'expected' => ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz']],
        ],
        'explanation' => [
            'en' => 'I loop from 1 to n and check divisibility with the remainder operator. I check "divisible by 15" first, because those numbers are also divisible by 3 and by 5. It runs in linear time, O(n), and uses O(n) space for the output.',
            'pt' => 'Percorro de 1 a n e confiro a divisibilidade com o operador de resto. Confiro "divisível por 15" primeiro, porque esses números também são divisíveis por 3 e por 5. Roda em tempo linear, O(n), e usa O(n) de espaço para a saída.',
        ],
        'solution' => <<<'CODE'
            function fizzBuzz(n) {
              const out = [];
              for (let i = 1; i <= n; i++) {
                out.push(i % 15 === 0 ? 'FizzBuzz' : i % 3 === 0 ? 'Fizz' : i % 5 === 0 ? 'Buzz' : String(i));
              }
              return out;
            }
            CODE,
    ],
    [
        'slug' => 'valid-palindrome',
        'title' => 'Valid Palindrome',
        'difficulty' => 'easy',
        'summary' => 'Strings, limpeza de entrada e dois ponteiros.',
        'signature' => ['name' => 'isPalindrome', 'params' => ['s' => 'string'], 'returns' => 'bool'],
        'statement' => [
            'A phrase is a {{palindrome}} if it reads the same forward and backward after converting all uppercase letters into lowercase and removing all {{non-alphanumeric}} characters.',
            'Given a string `s`, return `true` if it is a palindrome, or `false` {{otherwise}}.',
        ],
        'examples' => [
            ['input' => 's = "A man, a plan, a canal: Panama"', 'output' => 'true', 'explanation' => '"amanaplanacanalpanama" reads the same backward.'],
            ['input' => 's = "race a car"', 'output' => 'false'],
        ],
        'constraints' => ['0 <= s.length <= 2 * 10^5', '`s` contains only printable ASCII characters.'],
        'glossary' => [
            'palindrome' => 'Palíndromo: lido igual de trás para frente ("arara").',
            'non-alphanumeric' => 'Que não é letra nem número: espaços, vírgulas, dois-pontos.',
            'otherwise' => 'Caso contrário, senão.',
        ],
        'hints' => [
            'First build a clean version of the string: lowercase, letters and digits only.',
            'Can you compare characters from both ends, moving two pointers toward the middle?',
        ],
        'tests' => [
            ['args' => ['A man, a plan, a canal: Panama'], 'expected' => true],
            ['args' => ['race a car'], 'expected' => false],
            ['args' => [' '], 'expected' => true],
            ['args' => ['0P'], 'expected' => false],
            ['args' => ['Was it a car or a cat I saw?'], 'expected' => true],
        ],
        'explanation' => [
            'en' => 'I keep only letters and digits, in lowercase, and then compare the string from both ends with two pointers. If any pair is different, it is not a palindrome. It is O(n) in time and O(n) in space for the cleaned string.',
            'pt' => 'Mantenho só letras e dígitos, em minúsculas, e comparo a string pelas duas pontas com dois ponteiros. Se algum par for diferente, não é palíndromo. É O(n) em tempo e O(n) em espaço para a string limpa.',
        ],
        'solution' => <<<'CODE'
            function isPalindrome(s) {
              const clean = s.toLowerCase().replace(/[^a-z0-9]/g, '');
              for (let left = 0, right = clean.length - 1; left < right; left++, right--) {
                if (clean[left] !== clean[right]) return false;
              }
              return true;
            }
            CODE,
    ],
    [
        'slug' => 'two-sum',
        'title' => 'Two Sum',
        'difficulty' => 'easy',
        'summary' => 'O mais pedido em entrevistas: arrays e hash map.',
        'signature' => ['name' => 'twoSum', 'params' => ['nums' => 'int[]', 'target' => 'int'], 'returns' => 'int[]'],
        'statement' => [
            'Given an array of integers `nums` and an integer `target`, return the {{indices}} of the two numbers that {{add up to}} `target`, in {{ascending order}}.',
            'You may {{assume}} that each input has exactly one solution, and you may not use the same element twice.',
        ],
        'examples' => [
            ['input' => 'nums = [2, 7, 11, 15], target = 9', 'output' => '[0, 1]', 'explanation' => 'nums[0] + nums[1] == 9.'],
            ['input' => 'nums = [3, 2, 4], target = 6', 'output' => '[1, 2]'],
        ],
        'constraints' => ['2 <= nums.length <= 10^4', '-10^9 <= nums[i] <= 10^9', 'Only one valid answer exists.'],
        'glossary' => [
            'indices' => 'Plural de "index": as posições no array, começando em 0.',
            'add up to' => 'Somar, dar como soma. "Add up to 9" = somar 9.',
            'ascending order' => 'Ordem crescente, do menor para o maior.',
            'assume' => 'Supor, partir do princípio de que.',
        ],
        'hints' => [
            'A brute-force solution checks every pair. What is its time complexity?',
            'For each number, which other number do you need? Can a hash map remember what you have already seen?',
        ],
        'tests' => [
            ['args' => [[2, 7, 11, 15], 9], 'expected' => [0, 1]],
            ['args' => [[3, 2, 4], 6], 'expected' => [1, 2]],
            ['args' => [[3, 3], 6], 'expected' => [0, 1]],
            ['args' => [[-1, -2, -3, -4, -5], -8], 'expected' => [2, 4]],
            ['args' => [[0, 4, 3, 0], 0], 'expected' => [0, 3]],
        ],
        'explanation' => [
            'en' => 'I walk through the array once and keep a hash map from each value to its index. For each number, I look up its complement, target minus the number. If it is already in the map, I return both indices. That is O(n) time and O(n) extra space, instead of O(n²) for checking every pair.',
            'pt' => 'Percorro o array uma vez e guardo num hash map cada valor com seu índice. Para cada número, procuro o complemento, target menos o número. Se já está no mapa, devolvo os dois índices. Isso é O(n) em tempo e O(n) de espaço extra, em vez de O(n²) checando todos os pares.',
        ],
        'solution' => <<<'CODE'
            function twoSum(nums, target) {
              const seen = new Map();
              for (let i = 0; i < nums.length; i++) {
                if (seen.has(target - nums[i])) return [seen.get(target - nums[i]), i];
                seen.set(nums[i], i);
              }
              return [];
            }
            CODE,
    ],
    [
        'slug' => 'balanced-brackets',
        'title' => 'Balanced Brackets',
        'difficulty' => 'easy',
        'summary' => 'Pilha: a pergunta que também aparece na entrevista simulada.',
        'signature' => ['name' => 'isBalanced', 'params' => ['s' => 'string'], 'returns' => 'bool'],
        'statement' => [
            'Given a string `s` containing just the characters `(`, `)`, `[`, `]`, `{` and `}`, determine if the input string is {{balanced}}.',
            'A string is balanced if every opening bracket is closed by the same type of bracket, and brackets are closed in the correct order. An {{empty string}} is balanced.',
        ],
        'examples' => [
            ['input' => 's = "{[]}"', 'output' => 'true'],
            ['input' => 's = "([)]"', 'output' => 'false', 'explanation' => 'The square bracket is closed after the parenthesis that was opened inside it.'],
        ],
        'constraints' => ['0 <= s.length <= 10^4', '`s` consists of brackets only.'],
        'glossary' => [
            'balanced' => 'Balanceada: cada abertura tem o fechamento certo, na ordem certa.',
            'empty string' => 'String vazia, "".',
        ],
        'hints' => [
            'The last bracket opened must be the first one closed. Which data structure works like that?',
            'When you see a closing bracket, check the top of your stack. What if the stack is empty?',
        ],
        'tests' => [
            ['args' => ['()'], 'expected' => true],
            ['args' => ['()[]{}'], 'expected' => true],
            ['args' => ['{[]}'], 'expected' => true],
            ['args' => ['(]'], 'expected' => false],
            ['args' => ['([)]'], 'expected' => false],
            ['args' => [''], 'expected' => true],
            ['args' => ['(('], 'expected' => false],
            ['args' => ['){'], 'expected' => false],
        ],
        'explanation' => [
            'en' => 'I use a stack. I push every opening bracket, and for every closing bracket I pop the top and check that it is the matching type. At the end the stack must be empty. It is O(n) in time and O(n) in space in the worst case.',
            'pt' => 'Uso uma pilha. Empilho cada abertura e, a cada fechamento, desempilho o topo e confiro se é do mesmo tipo. No fim, a pilha precisa estar vazia. É O(n) em tempo e O(n) em espaço no pior caso.',
        ],
        'solution' => <<<'CODE'
            function isBalanced(s) {
              const pairs = { ')': '(', ']': '[', '}': '{' };
              const stack = [];
              for (const c of s) {
                if (c in pairs) {
                  if (stack.pop() !== pairs[c]) return false;
                } else {
                  stack.push(c);
                }
              }
              return stack.length === 0;
            }
            CODE,
    ],
    [
        'slug' => 'first-unique-character',
        'title' => 'First Unique Character',
        'difficulty' => 'easy',
        'summary' => 'Contagem de frequência com um mapa.',
        'signature' => ['name' => 'firstUniqueChar', 'params' => ['s' => 'string'], 'returns' => 'int'],
        'statement' => [
            'Given a string `s`, find the first {{non-repeating}} character in it and return its index. If it does not {{exist}}, return `-1`.',
        ],
        'examples' => [
            ['input' => 's = "loveleetcode"', 'output' => '2', 'explanation' => '"v" appears only once, at index 2.'],
            ['input' => 's = "aabb"', 'output' => '-1'],
        ],
        'constraints' => ['0 <= s.length <= 10^5', '`s` consists of only lowercase English letters.'],
        'glossary' => [
            'non-repeating' => 'Que não se repete: aparece uma única vez.',
            'exist' => 'Existir. "If it does not exist" = se não houver.',
        ],
        'hints' => [
            'Count how many times each character appears first, in one pass.',
            'Then walk through the string again and return the first index with a count of one.',
        ],
        'tests' => [
            ['args' => ['leetcode'], 'expected' => 0],
            ['args' => ['loveleetcode'], 'expected' => 2],
            ['args' => ['aabb'], 'expected' => -1],
            ['args' => ['z'], 'expected' => 0],
            ['args' => [''], 'expected' => -1],
        ],
        'explanation' => [
            'en' => 'I make two passes. In the first one I count how many times each character appears, using a map. In the second one I return the first index whose count is one. Two linear passes give O(n) time, and the map holds at most 26 letters, so the extra space is constant.',
            'pt' => 'Faço duas passadas. Na primeira, conto quantas vezes cada caractere aparece, com um mapa. Na segunda, devolvo o primeiro índice com contagem um. Duas passadas lineares dão O(n) em tempo, e o mapa guarda no máximo 26 letras, então o espaço extra é constante.',
        ],
        'solution' => <<<'CODE'
            function firstUniqueChar(s) {
              const count = new Map();
              for (const c of s) count.set(c, (count.get(c) ?? 0) + 1);
              for (let i = 0; i < s.length; i++) {
                if (count.get(s[i]) === 1) return i;
              }
              return -1;
            }
            CODE,
    ],
    [
        'slug' => 'merge-intervals',
        'title' => 'Merge Intervals',
        'difficulty' => 'medium',
        'summary' => 'Ordenação e intervalos: comum em system design de agendas.',
        'signature' => ['name' => 'merge', 'params' => ['intervals' => 'int[][]'], 'returns' => 'int[][]'],
        'statement' => [
            'Given an array of `intervals` where `intervals[i] = [start, end]`, merge all {{overlapping}} intervals and return an array of the non-overlapping intervals that cover all the intervals in the input.',
            'The input is not {{sorted}}. Return the result sorted by start. Intervals that only touch, like `[1, 4]` and `[4, 5]`, also overlap.',
        ],
        'examples' => [
            ['input' => 'intervals = [[1, 3], [2, 6], [8, 10], [15, 18]]', 'output' => '[[1, 6], [8, 10], [15, 18]]', 'explanation' => '[1, 3] and [2, 6] overlap, so they become [1, 6].'],
            ['input' => 'intervals = [[1, 4], [4, 5]]', 'output' => '[[1, 5]]'],
        ],
        'constraints' => ['0 <= intervals.length <= 10^4', '0 <= start <= end <= 10^4'],
        'glossary' => [
            'overlapping' => 'Que se sobrepõem, que têm uma parte em comum.',
            'sorted' => 'Ordenado.',
        ],
        'hints' => [
            'Sort the intervals by their start first. What does that guarantee?',
            'Keep the last merged interval. If the next one starts before it ends, extend it; otherwise, start a new one.',
        ],
        'tests' => [
            ['args' => [[[1, 3], [2, 6], [8, 10], [15, 18]]], 'expected' => [[1, 6], [8, 10], [15, 18]]],
            ['args' => [[[1, 4], [4, 5]]], 'expected' => [[1, 5]]],
            ['args' => [[[4, 7], [1, 4]]], 'expected' => [[1, 7]]],
            ['args' => [[[1, 4], [2, 3]]], 'expected' => [[1, 4]]],
            ['args' => [[]], 'expected' => []],
            ['args' => [[[5, 6]]], 'expected' => [[5, 6]]],
        ],
        'explanation' => [
            'en' => 'I sort the intervals by start, then go through them once. If the current interval starts before the last merged one ends, I extend the end; otherwise I add it as a new interval. Sorting dominates, so it is O(n log n) in time and O(n) in space for the output.',
            'pt' => 'Ordeno os intervalos pelo início e passo por eles uma vez. Se o intervalo atual começa antes de o último mesclado terminar, estendo o fim; senão, adiciono como novo intervalo. A ordenação domina, então é O(n log n) em tempo e O(n) em espaço para a saída.',
        ],
        'solution' => <<<'CODE'
            function merge(intervals) {
              const sorted = [...intervals].sort((a, b) => a[0] - b[0]);
              const merged = [];
              for (const [start, end] of sorted) {
                const last = merged[merged.length - 1];
                if (last && start <= last[1]) last[1] = Math.max(last[1], end);
                else merged.push([start, end]);
              }
              return merged;
            }
            CODE,
    ],
];
