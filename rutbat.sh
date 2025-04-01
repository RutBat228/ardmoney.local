#!/bin/bash
REPO_URL="https://github.com/RutBat228/rutapps.git"
REPO_DIR="$HOME/rutapps"
TV_LIST_FILE="$HOME/.tv_list"
CONNECTED_IP=""

# Цветовые коды
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'
BOLD='\033[1m'
UNDERLINE='\033[4m'

# Иконки
ICON_OK="${GREEN}✔${NC}"
ICON_ERR="${RED}✖${NC}"
ICON_INFO="${CYAN}ℹ${NC}"
ICON_WARN="${YELLOW}⚠${NC}"

# Функция отрисовки заголовка
draw_title() {
    clear
    echo -e "${CYAN}╔════════════════════════════════════════════╗"
    echo -e "║${BOLD}${YELLOW}       RutBat TV Manager v2.1${NC}${CYAN}             ║"
    echo -e "╚════════════════════════════════════════════╝${NC}"
}

# Валидация IP-адреса
validate_ip() {
    local ip=$1
    if [[ $ip =~ ^([0-9]{1,3}\.){3}[0-9]{1,3}$ ]]; then
        return 0
    else
        return 1
    fi
}

# Проверка и установка ADB
check_and_install_adb() {
    if ! command -v adb &> /dev/null; then
        echo -e "\n${ICON_INFO} Установка ADB..."
        apt update > /dev/null 2>&1 
        apt --assume-yes install wget > /dev/null 2>&1
        wget https://github.com/MasterDevX/Termux-ADB/raw/master/InstallTools.sh -q
        bash InstallTools.sh > /dev/null 2>&1
        rm InstallTools.sh
        
        if command -v adb &> /dev/null; then
            echo -e "${ICON_OK} ADB успешно установлен"
        else
            echo -e "${ICON_ERR} Не удалось установить ADB"
            exit 1
        fi
    fi
}

# Обновление репозитория
check_and_update_repo() {
    if [ -d "$REPO_DIR" ]; then
        echo -e "\n${ICON_INFO} Обновление репозитория..."
        cd "$REPO_DIR"
        git pull
    else
        echo -e "\n${ICON_INFO} Клонирование репозитория..."
        git clone "$REPO_URL" "$REPO_DIR"
    fi
}

# Меню обновления репозитория
update_repo_menu() {
    draw_title
    check_and_update_repo
    echo -e "\n${ICON_OK} Репозиторий обновлён"
    sleep 2
}

# Выбор IP из истории (нумерованное отображение)
select_ip_from_history() {
    if [ ! -s "$TV_LIST_FILE" ]; then
        echo -e "${ICON_ERR} История подключений пуста"
        sleep 2
        return 1
    fi

    draw_title
    echo -e "${BOLD}История подключений:${NC}"
    nl "$TV_LIST_FILE"

    # Получаем количество записей
    local total
    total=$(wc -l < "$TV_LIST_FILE" | tr -d ' ')
    
    while true; do
        read -p "Выберите номер ТВ (0 для отмены): " choice
        if [[ "$choice" =~ ^[0-9]+$ ]]; then
            if [ "$choice" -eq 0 ]; then
                return 1
            elif [ "$choice" -le "$total" ]; then
                local ip
                ip=$(sed -n "${choice}p" "$TV_LIST_FILE")
                connect_to_tv "$ip" && return 0
            else
                echo -e "${ICON_ERR} Неверный номер"
            fi
        else
            echo -e "${ICON_ERR} Введите числовое значение"
        fi
    done
}

# Подключение к ТВ с возможностью повторной попытки
connect_to_tv() {
    local ip=$1
    if ! validate_ip "$ip"; then
        echo -e "${ICON_ERR} Неверный формат IP-адреса"
        sleep 2
        return 1
    fi
    
    echo -e "\n${ICON_INFO} Подключение к ${ip}..."
    adb connect "${ip}:5555" > /dev/null 2>&1
    
    if adb devices | grep -q "${ip}:5555.*device"; then
        CONNECTED_IP=$ip
        [ ! -f "$TV_LIST_FILE" ] && touch "$TV_LIST_FILE"
        grep -q "^$ip$" "$TV_LIST_FILE" || echo "$ip" >> "$TV_LIST_FILE"
        return 0
    else
        echo -e "${ICON_ERR} Ошибка подключения к ${ip}"
        read -p "Попробовать переподключиться? (y/n): " retry
        if [[ "$retry" =~ ^[Yy]$ ]]; then
            connect_to_tv "$ip"
            return $?
        else
            sleep 2
            return 1
        fi
    fi
}

# Проверка свободного места
check_storage() {
    echo -e "\n${ICON_INFO} Проверка свободного места..."
    adb shell df /data | awk 'NR==2 {printf "Свободно: %s\n", $4}'
    read -n 1 -s -r -p "Нажмите любую клавишу для продолжения..."
}

# Удаление IP из истории
delete_ip_from_history() {
    draw_title
    echo -e "${BOLD}Сохранённые IP-адреса:${NC}"
    nl "$TV_LIST_FILE"
    
    while true; do
        read -p "Введите номер для удаления (0 для отмены): " num
        if [[ "$num" =~ ^[0-9]+$ ]]; then
            if [ "$num" -eq 0 ]; then
                return
            elif [ "$num" -le "$(wc -l < "$TV_LIST_FILE" | tr -d ' ')" ]; then
                sed -i "${num}d" "$TV_LIST_FILE"
                echo -e "${ICON_OK} Адрес удалён"
                sleep 1
                return
            else
                echo -e "${ICON_ERR} Неверный номер"
            fi
        else
            echo -e "${ICON_ERR} Введите числовое значение"
        fi
    done
}

# Страница управления ТВ (меню удаления приложений)

tv_management_menu() {
    while true; do
        draw_title
        echo -e "${BOLD}Установленные приложения (без системных):${NC}"
        # Используем опцию -3, чтобы получить только пользовательские приложения
        mapfile -t packages < <(adb shell pm list packages -3 | sed 's/package://g')
        for i in "${!packages[@]}"; do
            printf "%2d) %s\n" $((i+1)) "${packages[$i]}"
        done
        echo -e "\n${RED}Введите номер приложения для удаления, либо выберите опцию:"
        echo -e "${GREEN}s${NC} - проверить место"
        echo -e "${BLUE}r${NC} - удалить IP из истории"
        echo -e "${YELLOW}k${NC} - отключиться от ТВ"
        echo -e "${CYAN}b${NC} - назад${NC}"
        read -p "Ваш выбор: " choice
        if [[ "$choice" =~ ^[0-9]+$ ]]; then
            if [ "$choice" -ge 1 ] && [ "$choice" -le "${#packages[@]}" ]; then
                adb uninstall "${packages[$((choice-1))]}"
                read -n 1 -s -r -p "Нажмите любую клавишу для продолжения..."
            else
                echo -e "${ICON_ERR} Неверный номер"
                sleep 1
            fi
        else
            case "$choice" in
                s) check_storage ;;
                r) delete_ip_from_history ;;
                k) adb kill-server; CONNECTED_IP=""; return ;;
                b) return ;;
                *) echo -e "${ICON_ERR} Неверный выбор"; sleep 1 ;;
            esac
        fi
    done
}


# Установка APK с нумерованным меню
install_apk() {
    local apk=$1
    echo -e "\n${ICON_INFO} Проверка свободного места..."
    local free_space
    free_space=$(adb shell df /data | awk 'NR==2 {print $4}')
    local apk_size
    apk_size=$(du -s "$apk" | awk '{print $1}')
    
    if [ "$apk_size" -gt "${free_space//[!0-9]/}" ]; then
        echo -e "${ICON_ERR} Недостаточно места на устройстве"
        return 1
    fi
    
    echo -e "${ICON_INFO} Установка $(basename "$apk")..."
    adb install -r "$apk"
}

# Меню установки APK с числовой нумерацией
apk_menu() {
    while true; do
        draw_title
        echo -e "${BOLD}Доступные приложения:${NC}"
        local apk_files=("$REPO_DIR"/*.apk)
        for i in "${!apk_files[@]}"; do
            local apk_name
            apk_name=$(basename "${apk_files[$i]}")
            printf "%2d) %s\n" $((i+1)) "$apk_name"
        done

        echo -e "\n${CYAN}╔════════════════════════════════════════════╗"
        echo -e "║ ${GREEN}u${NC}) Управление ТВ                          ${CYAN}║"
        echo -e "╚════════════════════════════════════════════╝${NC}"
        
        read -p "Выберите номер приложения для установки или u: " choice
        if [ "$choice" == "u" ]; then
            return
        elif [[ "$choice" =~ ^[0-9]+$ ]]; then
            if [ "$choice" -ge 1 ] && [ "$choice" -le "${#apk_files[@]}" ]; then
                install_apk "${apk_files[$((choice-1))]}"
                read -n 1 -s -r -p "Нажмите любую клавишу для продолжения..."
            else
                echo -e "${ICON_ERR} Неверный выбор"
                sleep 1
            fi
        else
            echo -e "${ICON_ERR} Введите числовое значение или u"
            sleep 1
        fi
    done
}

# Главное меню
main_menu() {
    while true; do
        draw_title
        echo -e "${CYAN}╔════════════════════════════════════════════╗"
        echo -e "║${BOLD} Основное меню${NC}${CYAN}                             ║"
        echo -e "╠════════════════════════════════════════════╣"
        echo -e "║ ${GREEN}u${NC}) Обновить базу приложений            ${CYAN}║"
        echo -e "║ ${YELLOW}c${NC}) Подключиться к ТВ                  ${CYAN}║"
        echo -e "║ ${RED}q${NC}) Выход                              ${CYAN}║"
        echo -e "╚════════════════════════════════════════════╝${NC}"
        
        read -p "Выберите действие (u/c/q): " choice
        case $choice in
            u) update_repo_menu ;;
            c) connect_menu ;;
            q) exit 0 ;;
            *) echo -e "${ICON_ERR} Неверный выбор"; sleep 1 ;;
        esac
    done
}

# Меню подключения к ТВ с возможностью подключения по номеру из истории
connect_menu() {
    while true; do
        draw_title
        echo -e "${BOLD}История подключений:${NC}"
        if [ -s "$TV_LIST_FILE" ]; then
            nl "$TV_LIST_FILE"
            echo -e "\n${GREEN}Пожалуйста, выберите порядковый номер ТВ для подключения, либо введите команду: ${NC}"
        else
            echo "Нет сохранённых ТВ"
        fi
        
        echo -e "\n${CYAN}╔════════════════════════════════════════════╗"
        echo -e "║   n - Новый ТВ                             ║"
        echo -e "║   h - Показать историю                     ║"
        echo -e "║   b - Назад                                ║"
        echo -e "╚════════════════════════════════════════════╝${NC}"
        read -p "Ваш выбор: " choice
        if [[ "$choice" =~ ^[0-9]+$ ]]; then
            total=$(wc -l < "$TV_LIST_FILE" | tr -d ' ')
            if [ "$choice" -ge 1 ] && [ "$choice" -le "$total" ]; then
                ip=$(sed -n "${choice}p" "$TV_LIST_FILE")
                connect_to_tv "$ip" && break
            else
                echo -e "${ICON_ERR} Неверный номер"
                sleep 1
            fi
        else
            case "$choice" in
                n) read -p "Введите IP: " ip
                   connect_to_tv "$ip" && break ;;
                h) select_ip_from_history && break ;;
                b) return ;;
                *) echo -e "${ICON_ERR} Неверный выбор"; sleep 1 ;;
            esac
        fi
    done
    
    if [ -n "$CONNECTED_IP" ]; then
        apk_menu
        tv_management_menu
    fi
}

# Инициализация и запуск
check_and_install_adb
check_and_update_repo
main_menu
