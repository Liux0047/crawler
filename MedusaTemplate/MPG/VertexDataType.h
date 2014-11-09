#ifndef VERTEXDATATYPE_H
#define VERTEXDATATYPE_H

#include <cutil.h>
#include <cuda_runtime.h>
#include "../MedusaRT/MessageArrayManager.h"
#include "../MedusaRT/GraphConverter.h"


/**
* @dev under development, should be automatically generated
*/
struct VertexArray
{
	int *edge_count;
	MVT *level;
	int *msg_index;
	int *pg_edge_num;

#ifdef HY
	int *edge_index;
#else ifdef AA
	int *edge_index;
#endif

	int size;
	VertexArray();
	/**
	* build the vertex array from a graph
	*/
	void build(GraphIR &graph);
	void resize(int num);
	void assign(int i, Vertex v);/* assign to element i of this array using a Vertex object */
};


/**
* @dev under development, should be automatically generated
*/
struct D_VertexArray
{
	int *d_edge_count;
	MVT *d_level;
	int *d_msg_index;
	int *d_pg_edge_num;

#ifdef HY
	int *d_edge_index;

#else ifdef AA
	int *d_edge_index;
#endif

	int size;
	void Fill(VertexArray &varr);
	void Dump(VertexArray &varr);
};

#endif