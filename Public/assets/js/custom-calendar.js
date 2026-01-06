/**
 * CustomCalendar - Calendário customizado para substituir input type="date"
 * Compatível com iOS/iPhone e todos os navegadores modernos
 */
class CustomCalendar {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            disableWeekends: options.disableWeekends || false,
            format: options.format || 'dd/mm/yyyy',
            locale: options.locale || 'pt-BR',
            ...options
        };
        
        this.currentDate = new Date();
        this.selectedDate = null;
        this.minDate = null;
        this.maxDate = null;
        this.calendarContainer = null;
        this.isOpen = false;
        
        this.init();
    }
    
    init() {
        // Ler atributos do input original
        if (this.input.hasAttribute('min')) {
            this.minDate = new Date(this.input.getAttribute('min'));
        }
        if (this.input.hasAttribute('max')) {
            this.maxDate = new Date(this.input.getAttribute('max'));
        }
        
        // Se já tem valor, definir como selecionado
        if (this.input.value) {
            this.selectedDate = new Date(this.input.value);
            this.currentDate = new Date(this.selectedDate);
        }
        
        // Criar wrapper e input visual
        this.createWrapper();
        
        // Adicionar event listeners
        this.attachEvents();
    }
    
    createWrapper() {
        // Criar wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-calendar-wrapper relative';
        
        // Criar input visual (readonly)
        const visualInput = document.createElement('input');
        visualInput.type = 'text';
        visualInput.readOnly = true;
        visualInput.className = this.input.className;
        visualInput.placeholder = this.input.placeholder || 'dd/mm/yyyy';
        visualInput.value = this.formatDate(this.selectedDate);
        visualInput.id = this.input.id ? this.input.id + '_visual' : '';
        
        // Copiar atributos importantes
        if (this.input.hasAttribute('required')) {
            visualInput.setAttribute('required', '');
        }
        
        // Esconder input original
        this.input.type = 'hidden';
        this.input.style.display = 'none';
        
        // Adicionar ao DOM
        this.input.parentNode.insertBefore(wrapper, this.input);
        wrapper.appendChild(this.input);
        wrapper.appendChild(visualInput);
        
        this.visualInput = visualInput;
        this.wrapper = wrapper;
    }
    
    attachEvents() {
        // Abrir calendário ao clicar no input visual
        this.visualInput.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });
        
        // Fechar ao clicar fora
        document.addEventListener('click', (e) => {
            if (this.isOpen && 
                !this.calendarContainer.contains(e.target) && 
                !this.visualInput.contains(e.target)) {
                this.close();
            }
        });
        
        // Fechar com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        if (this.isOpen) return;
        
        this.render();
        this.isOpen = true;
        
        // Posicionar calendário
        const rect = this.visualInput.getBoundingClientRect();
        const scrollY = window.scrollY || window.pageYOffset;
        const scrollX = window.scrollX || window.pageXOffset;
        
        this.calendarContainer.style.position = 'fixed';
        this.calendarContainer.style.zIndex = '9999';
        
        // Tentar posicionar abaixo do input
        let top = rect.bottom + scrollY + 5;
        let left = rect.left + scrollX;
        
        // Ajustar posição se sair da tela
        setTimeout(() => {
            const calendarRect = this.calendarContainer.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            
            // Ajustar horizontalmente
            if (calendarRect.right > viewportWidth) {
                left = viewportWidth - calendarRect.width - 10;
            }
            if (calendarRect.left < 0) {
                left = 10;
            }
            
            // Ajustar verticalmente
            if (calendarRect.bottom > viewportHeight + scrollY) {
                // Tentar acima do input
                top = rect.top + scrollY - calendarRect.height - 5;
                if (top < scrollY) {
                    // Se não couber acima, centralizar na tela
                    top = scrollY + (viewportHeight - calendarRect.height) / 2;
                }
            }
            
            this.calendarContainer.style.top = top + 'px';
            this.calendarContainer.style.left = left + 'px';
        }, 0);
        
        // Posição inicial
        this.calendarContainer.style.top = top + 'px';
        this.calendarContainer.style.left = left + 'px';
    }
    
    close() {
        if (!this.isOpen) return;
        
        if (this.calendarContainer) {
            this.calendarContainer.remove();
        }
        this.isOpen = false;
    }
    
    render() {
        // Remover calendário anterior se existir
        if (this.calendarContainer) {
            this.calendarContainer.remove();
        }
        
        // Criar container
        this.calendarContainer = document.createElement('div');
        this.calendarContainer.className = 'custom-calendar bg-white rounded-lg shadow-xl border border-gray-200 p-4';
        this.calendarContainer.style.width = '320px';
        this.calendarContainer.style.maxWidth = '90vw';
        
        // Header com navegação
        const header = this.createHeader();
        this.calendarContainer.appendChild(header);
        
        // Grid de dias
        const grid = this.createGrid();
        this.calendarContainer.appendChild(grid);
        
        // Adicionar ao body
        document.body.appendChild(this.calendarContainer);
    }
    
    createHeader() {
        const header = document.createElement('div');
        header.className = 'flex items-center justify-between mb-4';
        
        // Botão anterior
        const prevBtn = document.createElement('button');
        prevBtn.className = 'p-2 hover:bg-gray-100 rounded-lg transition-colors';
        prevBtn.innerHTML = '<i class="fas fa-chevron-left text-gray-600"></i>';
        prevBtn.addEventListener('click', () => this.navigateMonth(-1));
        prevBtn.setAttribute('aria-label', 'Mês anterior');
        
        // Mês/Ano atual
        const monthYear = document.createElement('div');
        monthYear.className = 'text-lg font-semibold text-gray-800';
        monthYear.textContent = this.getMonthYearString();
        this.monthYearElement = monthYear;
        
        // Botão próximo
        const nextBtn = document.createElement('button');
        nextBtn.className = 'p-2 hover:bg-gray-100 rounded-lg transition-colors';
        nextBtn.innerHTML = '<i class="fas fa-chevron-right text-gray-600"></i>';
        nextBtn.addEventListener('click', () => this.navigateMonth(1));
        nextBtn.setAttribute('aria-label', 'Próximo mês');
        
        header.appendChild(prevBtn);
        header.appendChild(monthYear);
        header.appendChild(nextBtn);
        
        return header;
    }
    
    createGrid() {
        const grid = document.createElement('div');
        grid.className = 'custom-calendar-grid';
        
        // Cabeçalho dos dias da semana
        const weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const weekdaysRow = document.createElement('div');
        weekdaysRow.className = 'grid grid-cols-7 gap-1 mb-2';
        weekdays.forEach(day => {
            const dayHeader = document.createElement('div');
            dayHeader.className = 'text-center text-xs font-semibold text-gray-600 py-1';
            dayHeader.textContent = day;
            weekdaysRow.appendChild(dayHeader);
        });
        grid.appendChild(weekdaysRow);
        
        // Dias do mês
        const daysRow = document.createElement('div');
        daysRow.className = 'grid grid-cols-7 gap-1';
        
        const firstDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
        const lastDay = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth() + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Criar 42 células (6 semanas)
        for (let i = 0; i < 42; i++) {
            const cellDate = new Date(startDate);
            cellDate.setDate(startDate.getDate() + i);
            
            const dayBtn = document.createElement('button');
            dayBtn.type = 'button';
            dayBtn.className = 'custom-calendar-day p-2 text-sm rounded-lg transition-colors';
            
            const isCurrentMonth = cellDate.getMonth() === this.currentDate.getMonth();
            const isToday = this.isSameDay(cellDate, today);
            const isSelected = this.selectedDate && this.isSameDay(cellDate, this.selectedDate);
            const isDisabled = this.isDateDisabled(cellDate);
            
            // Classes condicionais
            if (!isCurrentMonth) {
                dayBtn.className += ' text-gray-300';
            } else if (isDisabled) {
                dayBtn.className += ' text-gray-300 bg-gray-100 cursor-not-allowed';
                dayBtn.disabled = true;
            } else if (isSelected) {
                dayBtn.className += ' bg-blue-600 text-white font-semibold';
            } else if (isToday) {
                dayBtn.className += ' bg-blue-100 text-blue-700 font-semibold';
            } else {
                dayBtn.className += ' text-gray-700 hover:bg-gray-100';
            }
            
            dayBtn.textContent = cellDate.getDate();
            dayBtn.setAttribute('data-date', this.formatDateForAttribute(cellDate));
            
            if (!isDisabled) {
                dayBtn.addEventListener('click', () => this.selectDate(cellDate));
            }
            
            daysRow.appendChild(dayBtn);
        }
        
        grid.appendChild(daysRow);
        return grid;
    }
    
    navigateMonth(direction) {
        this.currentDate.setMonth(this.currentDate.getMonth() + direction);
        this.render();
    }
    
    selectDate(date) {
        this.selectedDate = new Date(date);
        this.currentDate = new Date(date);
        
        // Atualizar input original
        this.input.value = this.formatDateForInput(this.selectedDate);
        
        // Atualizar input visual
        this.visualInput.value = this.formatDate(this.selectedDate);
        
        // Disparar evento change no input original
        const event = new Event('change', { bubbles: true });
        this.input.dispatchEvent(event);
        
        // Fechar calendário
        this.close();
    }
    
    isDateDisabled(date) {
        // Verificar min date
        if (this.minDate) {
            const min = new Date(this.minDate);
            min.setHours(0, 0, 0, 0);
            if (date < min) return true;
        }
        
        // Verificar max date
        if (this.maxDate) {
            const max = new Date(this.maxDate);
            max.setHours(0, 0, 0, 0);
            if (date > max) return true;
        }
        
        // Verificar fins de semana
        if (this.options.disableWeekends) {
            const day = date.getDay();
            if (day === 0 || day === 6) return true; // Domingo ou Sábado
        }
        
        // Verificar feriados
        if (this.isFeriado(date)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica se uma data é feriado no Brasil
     */
    isFeriado(date) {
        const year = date.getFullYear();
        const month = date.getMonth() + 1; // getMonth() retorna 0-11
        const day = date.getDate();
        
        // Feriados fixos
        const feriadosFixos = [
            { month: 1, day: 1 },   // Ano Novo
            { month: 4, day: 21 },  // Tiradentes
            { month: 5, day: 1 },   // Dia do Trabalhador
            { month: 9, day: 7 },   // Independência do Brasil
            { month: 10, day: 12 }, // Nossa Senhora Aparecida
            { month: 11, day: 2 },  // Finados
            { month: 11, day: 15 }, // Proclamação da República
            { month: 12, day: 25 }  // Natal
        ];
        
        // Verificar feriados fixos
        for (const feriado of feriadosFixos) {
            if (month === feriado.month && day === feriado.day) {
                return true;
            }
        }
        
        // Calcular feriados móveis baseados na Páscoa
        const pascoa = this.calcularPascoa(year);
        const carnaval = this.calcularCarnaval(pascoa);
        const sextaSanta = this.calcularSextaSanta(pascoa);
        const corpusChristi = this.calcularCorpusChristi(pascoa);
        
        const feriadosMoveis = [
            carnaval,
            sextaSanta,
            pascoa,
            corpusChristi
        ];
        
        // Verificar feriados móveis
        for (const feriado of feriadosMoveis) {
            if (this.isSameDay(date, feriado)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calcula a data da Páscoa para um determinado ano (algoritmo de Meeus/Jones/Butcher)
     */
    calcularPascoa(year) {
        const a = year % 19;
        const b = Math.floor(year / 100);
        const c = year % 100;
        const d = Math.floor(b / 4);
        const e = b % 4;
        const f = Math.floor((b + 8) / 25);
        const g = Math.floor((b - f + 1) / 3);
        const h = (19 * a + b - d - g + 15) % 30;
        const i = Math.floor(c / 4);
        const k = c % 4;
        const l = (32 + 2 * e + 2 * i - h - k) % 7;
        const m = Math.floor((a + 11 * h + 22 * l) / 451);
        const month = Math.floor((h + l - 7 * m + 114) / 31);
        const day = ((h + l - 7 * m + 114) % 31) + 1;
        
        const pascoa = new Date(year, month - 1, day);
        pascoa.setHours(0, 0, 0, 0);
        return pascoa;
    }
    
    /**
     * Calcula a data do Carnaval (terça-feira antes da Quarta-feira de Cinzas, 47 dias antes da Páscoa)
     */
    calcularCarnaval(pascoa) {
        const carnaval = new Date(pascoa);
        carnaval.setDate(carnaval.getDate() - 47);
        carnaval.setHours(0, 0, 0, 0);
        return carnaval;
    }
    
    /**
     * Calcula a Sexta-feira Santa (2 dias antes da Páscoa)
     */
    calcularSextaSanta(pascoa) {
        const sextaSanta = new Date(pascoa);
        sextaSanta.setDate(sextaSanta.getDate() - 2);
        sextaSanta.setHours(0, 0, 0, 0);
        return sextaSanta;
    }
    
    /**
     * Calcula o Corpus Christi (60 dias após a Páscoa)
     */
    calcularCorpusChristi(pascoa) {
        const corpusChristi = new Date(pascoa);
        corpusChristi.setDate(corpusChristi.getDate() + 60);
        corpusChristi.setHours(0, 0, 0, 0);
        return corpusChristi;
    }
    
    isSameDay(date1, date2) {
        if (!date1 || !date2) return false;
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }
    
    formatDate(date) {
        if (!date) return '';
        const d = date.getDate().toString().padStart(2, '0');
        const m = (date.getMonth() + 1).toString().padStart(2, '0');
        const y = date.getFullYear();
        return `${d}/${m}/${y}`;
    }
    
    formatDateForInput(date) {
        if (!date) return '';
        const d = date.getDate().toString().padStart(2, '0');
        const m = (date.getMonth() + 1).toString().padStart(2, '0');
        const y = date.getFullYear();
        return `${y}-${m}-${d}`;
    }
    
    formatDateForAttribute(date) {
        return this.formatDateForInput(date);
    }
    
    getMonthYearString() {
        const months = [
            'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
            'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
        ];
        return `${months[this.currentDate.getMonth()]} ${this.currentDate.getFullYear()}`;
    }
    
    destroy() {
        this.close();
        if (this.wrapper) {
            this.wrapper.remove();
        }
    }
}

// Auto-inicialização quando o DOM estiver pronto
(function() {
    function initCalendars() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            // Verificar se já foi inicializado
            if (input.dataset.calendarInitialized === 'true') {
                return;
            }
            
            // Verificar se deve desabilitar fins de semana
            let disableWeekends = false;
            
            // Verificar atributo explícito
            if (input.hasAttribute('data-disable-weekends')) {
                disableWeekends = true;
            }
            
            // Verificar se há texto próximo mencionando "dias úteis" ou "segunda a sexta"
            if (!disableWeekends) {
                const parent = input.closest('div, form, section');
                if (parent) {
                    const text = parent.textContent || '';
                    if (text.includes('dias úteis') || 
                        text.includes('segunda a sexta') || 
                        text.includes('segunda-feira') ||
                        text.includes('fins de semana')) {
                        disableWeekends = true;
                    }
                }
            }
            
            // Criar calendário
            const calendar = new CustomCalendar(input, {
                disableWeekends: disableWeekends
            });
            
            // Marcar como inicializado
            input.dataset.calendarInitialized = 'true';
            input.dataset.calendarInstance = 'true';
        });
    }
    
    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendars);
    } else {
        initCalendars();
    }
    
    // Re-inicializar para elementos adicionados dinamicamente
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    if (node.tagName === 'INPUT' && node.type === 'date') {
                        initCalendars();
                    } else if (node.querySelectorAll) {
                        const dateInputs = node.querySelectorAll('input[type="date"]');
                        if (dateInputs.length > 0) {
                            initCalendars();
                        }
                    }
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
})();

