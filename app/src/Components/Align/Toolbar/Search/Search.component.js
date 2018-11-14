import React, {Component} from 'react';
import PropTypes from "prop-types";
import equal from "fast-deep-equal";
import ProjectActions from "../../../../Actions/Project.actions";
import SearchControlsComponent from "./SearchControls/SearchControls.component";

class SearchComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
    };

    constructor(props) {
        super(props);
        this.state = {
            elements: this.getElements(),
            searchResults: [],
            idList: [],
            featuredSearchResult: null,
            active: false,
            fulltext: ''
        };
    }


    componentDidMount() {

    }

    componentWillUnmount() {

    }
    componentDidUpdate(prevProps){
        if(!equal(this.state.elements,this.getElements(this.props.job.rows))){
            this.resetSearch();
        }
    }

    render() {
        const {active, featuredSearchResult, searchResults} = this.state;
        return (
            <div id="search">
                <form onSubmit={this.onPerformSearch}>
                    <input type="text" value={this.state.fulltext} onChange={this.onSearchChange}/>
                </form>
                {active && <SearchControlsComponent searchResults={searchResults}
                                                    featuredSearchResult={featuredSearchResult}
                                                    setFeatured={this.setFeatured}/>}
            </div>
        );
    }

    onPerformSearch = (e) => {
        e.preventDefault();
        this.setState({
            featuredSearchResult: this.state.featuredSearchResult++
        })
    };

    getElements = () =>{
        let elements = [];
        if(this.props.job.rows){
            this.props.job.rows.map((row, index) => {
                const source = {
                    content: row.source.content_clean ? row.source.content_clean.toLowerCase() : '',
                    type: 'source',
                    id: row.source.id,
                    index: index,
                };
                const target = {
                    content: row.target.content_clean ? row.target.content_clean.toLowerCase(): '',
                    type: 'target',
                    id: row.target.id,
                    index: index,
                };
                elements.push(source, target);
            });
        }
        return elements;
    };
    resetSearch = () =>{
        if(this.state.active){
            setTimeout(()=>{
                ProjectActions.emitSearchResults({
                    q: '' ,
                    searchResults: [],
                    idList: [],
                    featuredSearchResult: 0
                });
            },0)
        }
        this.setState({
            elements: this.getElements(this.props.job.rows),
            active: false,
            searchResults: [],
            featuredSearchResult: 0,
            idList: [],
            fulltext: ''
        });
    };
    onSearchChange = (event) => {
        let fulltext = event.target.value.toLowerCase();
        let active = false;
        let searchResults = [];
        let idList = [];

        if (fulltext.length > 0) {
            active = true;
            searchResults = this.state.elements.filter(function (item) {
                return item.content.indexOf(fulltext) !== -1;
            });

            idList = searchResults.map(e => {
                return e.id
            });

            ProjectActions.emitSearchResults({
                q: fulltext,
                searchResults: searchResults,
                idList: idList,
                featuredSearchResult: 0
            });
        }

        this.setState({
            fulltext: event.target.value,
            active: active,
            searchResults: searchResults,
            idList: idList,
            featuredSearchResult: 0
        })
    };
    setFeatured = (value) => {
        this.setState({
            featuredSearchResult: value
        })
    }
}

export default SearchComponent;
