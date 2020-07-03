import React, {useState} from "react";
import { 
	useTable, 
	useFilters, 
	useSortBy, 
	usePagination 
} from "react-table";
import {getChecklistPage, getIdentifyPage} from "../common/taxaUtils";

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome'
import { library } from "@fortawesome/fontawesome-svg-core";
import { faChevronRight,faChevronLeft,faChevronUp, faSearchPlus, faListUl, faSortAlphaDown, faSortAlphaUp, faTimesCircle } from '@fortawesome/free-solid-svg-icons'
library.add(faChevronRight,faChevronLeft,faChevronUp, faSearchPlus, faListUl, faSortAlphaDown, faSortAlphaUp, faTimesCircle)

const CLIENT_ROOT = "..";



export default function Table({ columns, data, pid }) {

	const [filterInput, setFilterInput] = useState("");
	
  const {
			getTableProps,
			getTableBodyProps,
			headerGroups,
			rows,
			page, // use page instead of rows, b/c we only want rows for the active page
			canPreviousPage,
			canNextPage,
			pageOptions,
			pageCount,
			gotoPage,
			nextPage,
			previousPage,
			setPageSize,
			prepareRow,
			setFilter,
			toggleSortBy,
			state: {
				pageIndex,
				pageSize,
				sortBy,
				groupBy,
				expanded,
				filters,
				selectedRowIds,
			},
		} = useTable({
    	columns,
    	data,
    	initialState: {
    		/*hiddenColumns: ["latcentroid"]*/
    		pageSize: 100
    	}
  	},
  	useFilters,
  	useSortBy,
  	usePagination
  );

	// Update the state when input changes
	const handleFilterChange = e => {
		const value = e.target.value || undefined;
		updateSearch(value);
	};
	function updateSearch(value) {
		setFilter("name",value);
		setFilterInput(value);
	}
	
	function getSortState(_headerGroup) {
		return (
			_headerGroup.isSorted
        ? _headerGroup.isSortedDesc
          ? "sort-desc"
          : "sort-asc"
          : ""
		)
	}

  return (
  	<div className="table-wrapper">
  		<div className="table-header">
  			
  			<div className="table-filter">
					<input
						value={filterInput}
						onChange={handleFilterChange}
						placeholder={"Filter locations by name"}
					/>
					{filters.length? 
							<FontAwesomeIcon icon="times-circle" 
								onClick={ () => updateSearch('')  }
							/>
							: ''	
					}
				</div>
        <span className="verticalSeparator"></span>
  			<div className="table-nav">
  			
  				<div className="table-sort">
						<button
							className={ "lngSort " + getSortState(headerGroups[0].headers[2]) }
							onClick={() => toggleSortBy("longcentroid") }
						>
						{
							headerGroups[0].headers[2].isSorted ?
							(headerGroups[0].headers[2].isSortedDesc ?
							<img src={ CLIENT_ROOT + '/images/inventory/WEactive2x.png' } /> :
							<img src={ CLIENT_ROOT + '/images/inventory/EWactive2x.png' } />
							) :
								<img src={ CLIENT_ROOT + '/images/inventory/EW2x.png' } />
							}
						</button>
						<button
							className={ "nameSort " + getSortState(headerGroups[0].headers[0]) }
							onClick={() => toggleSortBy("name") }
						>{
							headerGroups[0].headers[0].isSorted ?
							(headerGroups[0].headers[0].isSortedDesc ?
							<FontAwesomeIcon icon="sort-alpha-up" /> :
							<FontAwesomeIcon icon="sort-alpha-down" />
							) :
								<FontAwesomeIcon icon="sort-alpha-down" />
							}
							</button>
					</div>
          <span className="verticalSeparator"></span>
					<div className="table-pag-top">
						{/* full rewind	<button onClick={() => gotoPage(0)} disabled={!canPreviousPage}>{'<<'}</button>{' '} */}
					  <button className="previous" onClick={() => previousPage()} disabled={!canPreviousPage}>
							<FontAwesomeIcon icon="chevron-left" />
						</button>{' '}
						<span className="rows-current">
						 {(pageIndex * pageSize) + 1}  - {
							(pageIndex + 1) * pageSize > rows.length? rows.length: (pageIndex + 1) * pageSize 
						 } 
						</span>
						<button className="next" onClick={() => nextPage()} disabled={!canNextPage}>
							<FontAwesomeIcon icon="chevron-right" />
						</button>{' '}
						{/*	full fast-forward <button onClick={() => gotoPage(pageCount - 1)} disabled={!canNextPage}> {'>>'} </button>{' '}*/}
						<span className="rows-results">of <span className="rows-total">{rows.length}</span> <span className="rows-label">results</span></span>
					</div>
				</div>	
  		</div>
			
			<table {...getTableProps()}>
			{/*	<thead>
          {headerGroups.map(headerGroup => (
            <tr {...headerGroup.getHeaderGroupProps()}>
                <th
                  {...headerGroup.headers[0].getHeaderProps(headerGroup.headers[0].getSortByToggleProps())}
                >
                  {headerGroup.headers[0].render("Header")}
                </th>
                <th
                  {...headerGroup.headers[1].getHeaderProps(headerGroup.headers[1].getSortByToggleProps())}
                >
                  {headerGroup.headers[1].render("Header")}
                </th>
            </tr>
          ))}
        </thead>
		*/ }
				<tbody {...getTableBodyProps()}>
					{page.map((row, i) => {
						prepareRow(row);
						let exploreUrl = getChecklistPage(CLIENT_ROOT, row.cells[1].value, pid);
						let identifyUrl = getIdentifyPage(CLIENT_ROOT, row.cells[1].value, pid);
						return (
							<tr {...row.getRowProps()}>
									<td {...row.cells[0].getCellProps()}>{row.cells[0].render("Cell")}</td>
									<td {...row.cells[1].getCellProps()}>
										<a href={ exploreUrl }>
											<FontAwesomeIcon icon="list-ul" />
										</a>
										<span className="verticalSeparator" />
										<a href={ identifyUrl }>
											<FontAwesomeIcon icon="search-plus" />
										</a>
									</td>
						
							</tr>
						);
					})}
				</tbody>
			</table>
			<div className="table-footer">
				<div className="table-pag-bottom">
				
					<button className="previous" onClick={() => previousPage()} disabled={!canPreviousPage}>
						<FontAwesomeIcon icon="chevron-left" />
						Previous { pageSize } 
					</button>{' '}
					
					<a className="back-to-top"
							onClick={() => window.scrollTo(0,0)}
					>
						<span className="back-to-top-label">Top</span>
						<FontAwesomeIcon icon="chevron-up" />
					</a>
					
					<button className="next" onClick={() => nextPage()} disabled={!canNextPage}>
						Next { pageSize }
						<FontAwesomeIcon icon="chevron-right" />
					</button>{' '}
				
				</div>
			</div>

			
    </div>
  );
}