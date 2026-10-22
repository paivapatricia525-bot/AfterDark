
document.addEventListener("DOMContentLoaded", () => {
    const busca = document.getElementById("busca-filme");
    const filtro = document.getElementById("filtro-categoria");
    const lista = document.getElementById("lista-filmes");
    const mensagem = document.getElementById("mensagem-sem-resultados");

    if (!lista) {
        console.warn("AfterDark: lista de filmes não encontrada.");
        return;
    }

    const cartoes = Array.from(
        lista.querySelectorAll(".cartao-filme")
    );

    /**
     * Normaliza texto para facilitar a comparação.
     */
    function normalizarTexto(texto) {
        return String(texto || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toLocaleLowerCase("pt-BR")
            .trim();
    }

    /**
     * Converte a lista de categorias do card em uma lista de slugs.
     */
    function obterCategorias(cartao) {
        const valor = cartao.dataset.categorias || "";

        return valor
            .trim()
            .split(/\s+/)
            .filter(Boolean);
    }

    /**
     * Aplica a pesquisa por título e o filtro de categoria.
     */
    function aplicarFiltros() {
        const textoBusca = normalizarTexto(busca?.value || "");
        const categoriaEscolhida = filtro?.value || "";

        let quantidadeVisivel = 0;

        cartoes.forEach((cartao) => {
            const titulo = normalizarTexto(
                cartao.querySelector("h2")?.textContent || ""
            );

            const categorias = obterCategorias(cartao);

            const correspondeTitulo = titulo.includes(textoBusca);

            const correspondeCategoria =
                categoriaEscolhida === "" ||
                categorias.includes(categoriaEscolhida);

            const deveExibir =
                correspondeTitulo && correspondeCategoria;

            cartao.hidden = !deveExibir;

            if (deveExibir) {
                quantidadeVisivel++;
            }
        });

        if (mensagem) {
            mensagem.hidden = quantidadeVisivel > 0;
        }
    }

    if (busca) {
        busca.addEventListener("input", aplicarFiltros);
    }

    if (filtro) {
        filtro.addEventListener("change", aplicarFiltros);
    }

    aplicarFiltros();
});
