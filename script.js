document.addEventListener('DOMContentLoaded', function() {
    const cycleContainers = document.querySelectorAll('.item-cycle');

    cycleContainers.forEach(container => {
        const items = container.querySelectorAll('.cycle-item');
        if(items.length > 1) {
            let currentIndex = 0;
            setInterval(() => {
                items[currentIndex].style.display = 'none';
                currentIndex = (currentIndex + 1) % items.length;
                items[currentIndex].style.display = 'block';
            }, 2000);
        }
    });
});