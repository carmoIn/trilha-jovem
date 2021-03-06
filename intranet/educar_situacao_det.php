<?php
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itaja�								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software P�blico Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itaja�			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  �  software livre, voc� pode redistribu�-lo e/ou	 *
	*	modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a vers�o 2 da	 *
	*	Licen�a   como  (a  seu  crit�rio)  qualquer  vers�o  mais  nova.	 *
	*																		 *
	*	Este programa  � distribu�do na expectativa de ser �til, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia impl�cita de COMERCIALI-	 *
	*	ZA��O  ou  de ADEQUA��O A QUALQUER PROP�SITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licen�a  P�blica  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Voc�  deve  ter  recebido uma c�pia da Licen�a P�blica Geral GNU	 *
	*	junto  com  este  programa. Se n�o, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsDetalhe.inc.php");
require_once ("include/clsBanco.inc.php");
require_once( "include/pmieducar/geral.inc.php" );

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} i-Educar - Situa&ccedil;&atilde;o" );
		$this->processoAp = "602";
	}
}

class indice extends clsDetalhe
{
	/**
	 * Titulo no topo da pagina
	 *
	 * @var int
	 */
	var $titulo;

	var $cod_situacao;
	var $ref_usuario_exc;
	var $ref_usuario_cad;
	var $nm_situacao;
	var $permite_emprestimo;
	var $descricao;
	var $situacao_padrao;
	var $situacao_emprestada;
	var $data_cadastro;
	var $data_exclusao;
	var $ativo;
	var $ref_cod_biblioteca;

	var $ref_cod_instituicao;
	var $ref_cod_escola;

	function Gerar()
	{
		@session_start();
		$this->pessoa_logada = $_SESSION['id_pessoa'];
		session_write_close();

		$this->titulo = "Situa&ccedil;&atilde;o - Detalhe";
		$this->addBanner( "imagens/nvp_top_intranet.jpg", "imagens/nvp_vert_intranet.jpg", "Intranet" );

		$this->cod_situacao=$_GET["cod_situacao"];

		$tmp_obj = new clsPmieducarSituacao( $this->cod_situacao );
		$registro = $tmp_obj->detalhe();

		if( ! $registro )
		{
			header( "location: educar_situacao_lst.php" );
			die();
		}

		if( class_exists( "clsPmieducarBiblioteca" ) )
		{
			$obj_ref_cod_biblioteca = new clsPmieducarBiblioteca( $registro["ref_cod_biblioteca"] );
			$det_ref_cod_biblioteca = $obj_ref_cod_biblioteca->detalhe();
			$registro["ref_cod_biblioteca"] = $det_ref_cod_biblioteca["nm_biblioteca"];
			$registro["ref_cod_instituicao"] = $det_ref_cod_biblioteca["ref_cod_instituicao"];
			$registro["ref_cod_escola"] = $det_ref_cod_biblioteca["ref_cod_escola"];
			if( $registro["ref_cod_instituicao"] )
			{
				$obj_ref_cod_instituicao = new clsPmieducarInstituicao( $registro["ref_cod_instituicao"] );
				$det_ref_cod_instituicao = $obj_ref_cod_instituicao->detalhe();
				$registro["ref_cod_instituicao"] = $det_ref_cod_instituicao["nm_instituicao"];
			}
			if( $registro["ref_cod_escola"] )
			{
				$obj_ref_cod_escola = new clsPmieducarEscola();
				$det_ref_cod_escola = array_shift($obj_ref_cod_escola->lista($registro["ref_cod_escola"]));
				$registro["ref_cod_escola"] = $det_ref_cod_escola["nome"];
			}
		}
		else
		{
			$registro["ref_cod_biblioteca"] = "Erro na gera&ccedil;&atilde;o";
			echo "<!--\nErro\nClasse n&atilde;o existente: clsPmieducarBiblioteca\n-->";
		}

		$obj_permissoes = new clsPermissoes();
		$nivel_usuario = $obj_permissoes->nivel_acesso($this->pessoa_logada);

		if( $registro["ref_cod_instituicao"] && $nivel_usuario == 1)
		{
			$this->addDetalhe( array( "Institui&ccedil;&atilde;o", "{$registro["ref_cod_instituicao"]}") );
		}
		if( $registro["ref_cod_escola"] && ($nivel_usuario == 1 || $nivel_usuario == 2) )
		{
			$this->addDetalhe( array( "Escola", "{$registro["ref_cod_escola"]}") );
		}
		if( $registro["ref_cod_biblioteca"] )
		{
			$this->addDetalhe( array( "Biblioteca", "{$registro["ref_cod_biblioteca"]}") );
		}
		if( $registro["nm_situacao"] )
		{
			$this->addDetalhe( array( "Situa&ccedil;&atilde;o", "{$registro["nm_situacao"]}") );
		}
		if( $registro["permite_emprestimo"] )
		{
			if ($registro["permite_emprestimo"] == 1)
				$registro["permite_emprestimo"] = "n&atilde;o";
			else if ($registro["permite_emprestimo"] == 2)
				$registro["permite_emprestimo"] = "sim";
			$this->addDetalhe( array( "Permite Empr&eacute;stimo", "{$registro["permite_emprestimo"]}") );
		}
		if( $registro["descricao"] )
		{
			$this->addDetalhe( array( "Descri&ccedil;&atilde;o", "{$registro["descricao"]}") );
		}
		if( $registro["situacao_padrao"] )
		{
			if ($registro["situacao_padrao"] == 0)
				$registro["situacao_padrao"] = "n&atilde;o";
			else if ($registro["situacao_padrao"] == 1)
				$registro["situacao_padrao"] = "sim";
			$this->addDetalhe( array( "Situa&ccedil;&atilde;o Padr&atilde;o", "{$registro["situacao_padrao"]}") );
		}
		if( $registro["situacao_emprestada"] )
		{
			if ($registro["situacao_emprestada"] == 0)
				$registro["situacao_emprestada"] = "n&atilde;o";
			else if ($registro["situacao_emprestada"] == 1)
				$registro["situacao_emprestada"] = "sim";
			$this->addDetalhe( array( "Situa&ccedil;&atilde;o Emprestada", "{$registro["situacao_emprestada"]}") );
		}

		$obj_permissoes = new clsPermissoes();
		if( $obj_permissoes->permissao_cadastra( 602, $this->pessoa_logada, 11 ) )
		{
		$this->url_novo = "educar_situacao_cad.php";
		$this->url_editar = "educar_situacao_cad.php?cod_situacao={$registro["cod_situacao"]}";
		}

		$this->url_cancelar = "educar_situacao_lst.php";
		$this->largura = "100%";
	}
}

// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();
?>