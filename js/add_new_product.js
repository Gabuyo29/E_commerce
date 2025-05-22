 function addSizeStockRow() {
            const container = document.getElementById('size-stock-container');
            const div = document.createElement('div');
            div.innerHTML = `
                <select name="sizes[]" required>
                    <option value="XS">XS</option>
                    <option value="S">S</option>
                    <option value="M">M</option>
                    <option value="L">L</option>
                    <option value="XL">XL</option>
                    <option value="XXL">XXL</option>
                </select>
                <input type="number" name="stocks[]" placeholder="Stock Quantity" required>
            `;
            container.appendChild(div);
        }