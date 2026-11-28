<?php

/*
| Go challenges. The format is described in javascript.php. The user writes
| the function in package main; a nil slice counts as an empty one.
*/

return [
    [
        'slug' => 'squares-of-a-sorted-array',
        'title' => 'Squares of a Sorted Array',
        'difficulty' => 'easy',
        'summary' => 'Dois ponteiros preenchendo a saída de trás para frente.',
        'signature' => ['name' => 'sortedSquares', 'params' => ['nums' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'Given an integer slice `nums` sorted in {{non-decreasing order}}, return a slice of the {{squares}} of each number, also sorted in non-decreasing order.',
            'Squaring and then sorting is O(n log n). Can you find an O(n) solution?',
        ],
        'examples' => [
            ['input' => 'nums = [-4, -1, 0, 3, 10]', 'output' => '[0, 1, 9, 16, 100]', 'explanation' => 'The squares are [16, 1, 0, 9, 100]; sorted, they become [0, 1, 9, 16, 100].'],
            ['input' => 'nums = [-7, -3, 2, 3, 11]', 'output' => '[4, 9, 9, 49, 121]'],
        ],
        'constraints' => ['1 <= len(nums) <= 10^4', '-10^4 <= nums[i] <= 10^4'],
        'glossary' => [
            'non-decreasing order' => 'Ordem não decrescente: crescente, permitindo valores iguais.',
            'squares' => 'Quadrados (o número vezes ele mesmo).',
        ],
        'hints' => [
            'The largest square is at one of the two ends of the slice. Why?',
            'Use a pointer at each end and fill the result from the last position to the first.',
        ],
        'tests' => [
            ['args' => [[-4, -1, 0, 3, 10]], 'expected' => [0, 1, 9, 16, 100]],
            ['args' => [[-7, -3, 2, 3, 11]], 'expected' => [4, 9, 9, 49, 121]],
            ['args' => [[1, 2, 3]], 'expected' => [1, 4, 9]],
            ['args' => [[-3, -2, -1]], 'expected' => [1, 4, 9]],
            ['args' => [[0]], 'expected' => [0]],
        ],
        'explanation' => [
            'en' => 'The input is sorted, so the largest square is at one of the two ends: a big negative or a big positive number. I keep a pointer at each end, compare their squares, write the larger one at the end of the result and move that pointer inward. One pass, so O(n) time, plus O(n) for the output.',
            'pt' => 'A entrada está ordenada, então o maior quadrado está numa das pontas: um negativo grande ou um positivo grande. Mantenho um ponteiro em cada ponta, comparo os quadrados, escrevo o maior no fim do resultado e movo esse ponteiro para dentro. Uma passada, então O(n) em tempo, mais O(n) para a saída.',
        ],
        'solution' => <<<'CODE'
            package main

            func sortedSquares(nums []int) []int {
            	result := make([]int, len(nums))
            	left, right := 0, len(nums)-1
            	for i := len(nums) - 1; i >= 0; i-- {
            		a, b := nums[left]*nums[left], nums[right]*nums[right]
            		if a > b {
            			result[i] = a
            			left++
            		} else {
            			result[i] = b
            			right--
            		}
            	}
            	return result
            }
            CODE,
    ],
    [
        'slug' => 'length-of-last-word',
        'title' => 'Length of Last Word',
        'difficulty' => 'easy',
        'summary' => 'Strings lidas do fim para o começo.',
        'signature' => ['name' => 'lengthOfLastWord', 'params' => ['s' => 'string'], 'returns' => 'int'],
        'statement' => [
            'Given a string `s` made of words and spaces, return the length of the last word in the string.',
            'A word is a {{maximal}} {{substring}} made of non-space characters only. The string may end with spaces.',
        ],
        'examples' => [
            ['input' => 's = "Hello World"', 'output' => '5', 'explanation' => 'The last word is "World", with length 5.'],
            ['input' => 's = "   fly me   to   the moon  "', 'output' => '4', 'explanation' => 'The last word is "moon".'],
        ],
        'constraints' => ['1 <= len(s) <= 10^4', '`s` consists of English letters and spaces.', 'There is at least one word in `s`.'],
        'glossary' => [
            'maximal' => 'Máximo possível: não dá para aumentar sem incluir um espaço.',
            'substring' => 'Substring: um trecho seguido da string.',
        ],
        'hints' => [
            'Start from the end of the string. Skip the trailing spaces first.',
            'Then count characters until you find a space or reach the beginning.',
        ],
        'tests' => [
            ['args' => ['Hello World'], 'expected' => 5],
            ['args' => ['   fly me   to   the moon  '], 'expected' => 4],
            ['args' => ['luffy is still joyboy'], 'expected' => 6],
            ['args' => ['a'], 'expected' => 1],
            ['args' => ['day   '], 'expected' => 3],
        ],
        'explanation' => [
            'en' => 'I read the string from the end. First I skip the trailing spaces, then I count characters until I find a space or the start of the string. I only read the part I need, so it is O(n) in the worst case and O(1) in space, without splitting the whole string.',
            'pt' => 'Leio a string do fim para o começo. Primeiro pulo os espaços do final, depois conto caracteres até achar um espaço ou o início da string. Só leio a parte que preciso, então é O(n) no pior caso e O(1) em espaço, sem separar a string inteira.',
        ],
        'solution' => <<<'CODE'
            package main

            func lengthOfLastWord(s string) int {
            	i := len(s) - 1
            	for i >= 0 && s[i] == ' ' {
            		i--
            	}
            	length := 0
            	for i >= 0 && s[i] != ' ' {
            		length++
            		i--
            	}
            	return length
            }
            CODE,
    ],
    [
        'slug' => 'merge-sorted-slices',
        'title' => 'Merge Two Sorted Slices',
        'difficulty' => 'easy',
        'summary' => 'O passo de junção do merge sort.',
        'signature' => ['name' => 'mergeSorted', 'params' => ['a' => 'int[]', 'b' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'You are given two integer slices `a` and `b`, each sorted in {{non-decreasing order}}. Return a single slice with all the elements of both, also sorted in non-decreasing order.',
            'Do not just {{append}} them and sort again: {{take advantage of}} the fact that both inputs are already sorted.',
        ],
        'examples' => [
            ['input' => 'a = [1, 2, 3], b = [2, 5, 6]', 'output' => '[1, 2, 2, 3, 5, 6]'],
            ['input' => 'a = [], b = [1]', 'output' => '[1]'],
        ],
        'constraints' => ['0 <= len(a), len(b) <= 200', '-10^9 <= a[i], b[j] <= 10^9'],
        'glossary' => [
            'non-decreasing order' => 'Ordem não decrescente: crescente, permitindo valores iguais.',
            'append' => 'Acrescentar ao final (append em Go adiciona elementos a um slice).',
            'take advantage of' => 'Aproveitar, tirar proveito de.',
        ],
        'hints' => [
            'Keep one index in each slice and always take the smaller of the two current elements.',
            'When one slice runs out, what do you do with the rest of the other one?',
        ],
        'tests' => [
            ['args' => [[1, 2, 3], [2, 5, 6]], 'expected' => [1, 2, 2, 3, 5, 6]],
            ['args' => [[], [1]], 'expected' => [1]],
            ['args' => [[4, 8], []], 'expected' => [4, 8]],
            ['args' => [[], []], 'expected' => []],
            ['args' => [[-3, 0, 0, 9], [-5, 0, 10]], 'expected' => [-5, -3, 0, 0, 0, 9, 10]],
        ],
        'explanation' => [
            'en' => 'I keep one index in each slice and repeatedly append the smaller of the two current elements to the result. When one slice runs out, I append everything left in the other one, which is already sorted. Each element is copied once, so it is O(n + m) time and O(n + m) space for the result.',
            'pt' => 'Mantenho um índice em cada slice e vou adicionando ao resultado o menor dos dois elementos atuais. Quando um slice acaba, adiciono tudo o que sobrou no outro, que já está ordenado. Cada elemento é copiado uma vez, então é O(n + m) em tempo e O(n + m) em espaço para o resultado.',
        ],
        'solution' => <<<'CODE'
            package main

            func mergeSorted(a []int, b []int) []int {
            	result := make([]int, 0, len(a)+len(b))
            	i, j := 0, 0
            	for i < len(a) && j < len(b) {
            		if a[i] <= b[j] {
            			result = append(result, a[i])
            			i++
            		} else {
            			result = append(result, b[j])
            			j++
            		}
            	}
            	result = append(result, a[i:]...)
            	return append(result, b[j:]...)
            }
            CODE,
    ],
    [
        'slug' => 'find-minimum-in-rotated-sorted-array',
        'title' => 'Find Minimum in Rotated Sorted Array',
        'difficulty' => 'medium',
        'summary' => 'Busca binária num array que foi girado.',
        'signature' => ['name' => 'findMin', 'params' => ['nums' => 'int[]'], 'returns' => 'int'],
        'statement' => [
            'A slice sorted in ascending order was {{rotated}} between 1 and `n` times. For example, `[0, 1, 2, 4, 5, 6, 7]` rotated 4 times becomes `[4, 5, 6, 7, 0, 1, 2]`.',
            'Given the rotated slice `nums` of unique elements, return its minimum element. You must write an algorithm that runs in O(log n) time.',
        ],
        'examples' => [
            ['input' => 'nums = [3, 4, 5, 1, 2]', 'output' => '1', 'explanation' => 'The original slice was [1, 2, 3, 4, 5], rotated 3 times.'],
            ['input' => 'nums = [11, 13, 15, 17]', 'output' => '11', 'explanation' => 'Rotated n times, the slice is back to the original order.'],
        ],
        'constraints' => ['1 <= len(nums) <= 5000', 'All the integers of `nums` are unique.'],
        'glossary' => [
            'rotated' => 'Rotacionado: os elementos do fim passaram para o começo.',
        ],
        'hints' => [
            'Compare the middle element with the last one. If it is larger, where must the minimum be?',
            'Keep shrinking the range [low, high] until it has one element.',
        ],
        'tests' => [
            ['args' => [[3, 4, 5, 1, 2]], 'expected' => 1],
            ['args' => [[4, 5, 6, 7, 0, 1, 2]], 'expected' => 0],
            ['args' => [[11, 13, 15, 17]], 'expected' => 11],
            ['args' => [[2, 1]], 'expected' => 1],
            ['args' => [[5]], 'expected' => 5],
            ['args' => [[-3, -1, -10, -8]], 'expected' => -10],
        ],
        'explanation' => [
            'en' => 'I use binary search. If the middle element is larger than the last one, the rotation point, and so the minimum, is to the right of the middle. Otherwise the minimum is the middle or to its left. I shrink the range until low and high meet, which is O(log n) time and O(1) space.',
            'pt' => 'Uso busca binária. Se o elemento do meio é maior que o último, o ponto de rotação, e portanto o mínimo, está à direita do meio. Senão, o mínimo é o próprio meio ou está à esquerda. Encolho o intervalo até baixo e alto se encontrarem, o que é O(log n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            package main

            func findMin(nums []int) int {
            	low, high := 0, len(nums)-1
            	for low < high {
            		middle := low + (high-low)/2
            		if nums[middle] > nums[high] {
            			low = middle + 1
            		} else {
            			high = middle
            		}
            	}
            	return nums[low]
            }
            CODE,
    ],
    [
        'slug' => 'daily-temperatures',
        'title' => 'Daily Temperatures',
        'difficulty' => 'medium',
        'summary' => 'Pilha monotônica: esperar pelo próximo maior.',
        'signature' => ['name' => 'dailyTemperatures', 'params' => ['temperatures' => 'int[]'], 'returns' => 'int[]'],
        'statement' => [
            'Given a slice of integers `temperatures` with the daily temperatures, return a slice `answer` such that `answer[i]` is the number of days you have to wait after day `i` to get a {{warmer}} temperature.',
            'If there is no future day for which this is possible, {{keep}} `answer[i] == 0`.',
        ],
        'examples' => [
            ['input' => 'temperatures = [73, 74, 75, 71, 69, 72, 76, 73]', 'output' => '[1, 1, 4, 2, 1, 1, 0, 0]', 'explanation' => 'After day 2 (75), the next warmer day is day 6 (76): 4 days later.'],
            ['input' => 'temperatures = [30, 60, 90]', 'output' => '[1, 1, 0]'],
        ],
        'constraints' => ['1 <= len(temperatures) <= 10^5', '30 <= temperatures[i] <= 100'],
        'glossary' => [
            'warmer' => 'Mais quente (comparativo de "warm").',
            'keep' => 'Manter, deixar como está.',
        ],
        'hints' => [
            'Checking every later day for each day is O(n²). Which days are still waiting for a warmer one?',
            'Keep a stack of indices whose answer is unknown. A warmer day answers every colder day on top of the stack.',
        ],
        'tests' => [
            ['args' => [[73, 74, 75, 71, 69, 72, 76, 73]], 'expected' => [1, 1, 4, 2, 1, 1, 0, 0]],
            ['args' => [[30, 40, 50, 60]], 'expected' => [1, 1, 1, 0]],
            ['args' => [[30, 60, 90]], 'expected' => [1, 1, 0]],
            ['args' => [[90, 80, 70]], 'expected' => [0, 0, 0]],
            ['args' => [[50, 50, 51]], 'expected' => [2, 1, 0]],
        ],
        'explanation' => [
            'en' => 'I keep a stack with the indices of the days that are still waiting for a warmer day, with the temperatures decreasing from bottom to top. For each new day, while it is warmer than the day on top of the stack, I pop that day and write the distance as its answer. Then I push the new day. Each index is pushed and popped once, so it is O(n) time and O(n) space.',
            'pt' => 'Mantenho uma pilha com os índices dos dias que ainda esperam um dia mais quente, com as temperaturas diminuindo da base para o topo. Para cada dia novo, enquanto ele é mais quente que o dia do topo, desempilho esse dia e escrevo a distância como resposta. Depois empilho o dia novo. Cada índice entra e sai da pilha uma vez, então é O(n) em tempo e O(n) em espaço.',
        ],
        'solution' => <<<'CODE'
            package main

            func dailyTemperatures(temperatures []int) []int {
            	answer := make([]int, len(temperatures))
            	stack := []int{}
            	for day, temperature := range temperatures {
            		for len(stack) > 0 && temperatures[stack[len(stack)-1]] < temperature {
            			previous := stack[len(stack)-1]
            			stack = stack[:len(stack)-1]
            			answer[previous] = day - previous
            		}
            		stack = append(stack, day)
            	}
            	return answer
            }
            CODE,
    ],
    [
        'slug' => 'jump-game',
        'title' => 'Jump Game',
        'difficulty' => 'medium',
        'summary' => 'Guloso: até onde dá para chegar.',
        'signature' => ['name' => 'canJump', 'params' => ['nums' => 'int[]'], 'returns' => 'bool'],
        'statement' => [
            'You are given an integer slice `nums`. You start at the first index, and each element is your maximum jump length from that position.',
            'Return `true` if you can {{reach}} the last index, or `false` otherwise. You can always jump {{fewer}} steps than the maximum.',
        ],
        'examples' => [
            ['input' => 'nums = [2, 3, 1, 1, 4]', 'output' => 'true', 'explanation' => 'Jump 1 step from index 0 to 1, then 3 steps to the last index.'],
            ['input' => 'nums = [3, 2, 1, 0, 4]', 'output' => 'false', 'explanation' => 'You always arrive at index 3, whose maximum jump is 0.'],
        ],
        'constraints' => ['1 <= len(nums) <= 10^4', '0 <= nums[i] <= 10^5'],
        'glossary' => [
            'reach' => 'Alcançar, chegar até.',
            'fewer' => 'Menos (para coisas contáveis: fewer steps = menos passos).',
        ],
        'hints' => [
            'Keep the farthest index you can reach so far.',
            'If the current index is beyond that farthest point, you are stuck. Otherwise, update it with i + nums[i].',
        ],
        'tests' => [
            ['args' => [[2, 3, 1, 1, 4]], 'expected' => true],
            ['args' => [[3, 2, 1, 0, 4]], 'expected' => false],
            ['args' => [[0]], 'expected' => true],
            ['args' => [[0, 1]], 'expected' => false],
            ['args' => [[2, 0, 0]], 'expected' => true],
            ['args' => [[1, 1, 0, 1]], 'expected' => false],
        ],
        'explanation' => [
            'en' => 'I go through the slice keeping the farthest index I can reach. If the current index is beyond that point, I can never get here, so the answer is false. Otherwise I update the farthest point with the current index plus its jump. If the loop finishes, the last index is reachable. It is a greedy, one-pass solution: O(n) time and O(1) space.',
            'pt' => 'Percorro o slice guardando o índice mais distante que consigo alcançar. Se o índice atual passou desse ponto, nunca chego aqui, então a resposta é false. Senão, atualizo o ponto mais distante com o índice atual mais o salto dele. Se o laço termina, o último índice é alcançável. É uma solução gulosa de uma passada: O(n) em tempo e O(1) em espaço.',
        ],
        'solution' => <<<'CODE'
            package main

            func canJump(nums []int) bool {
            	farthest := 0
            	for i, jump := range nums {
            		if i > farthest {
            			return false
            		}
            		if i+jump > farthest {
            			farthest = i + jump
            		}
            	}
            	return true
            }
            CODE,
    ],
];
